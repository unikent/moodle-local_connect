<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/stomp/Stomp.php');

/**
 * Proxy functions
 */

function lcproxy_getDBConnection() {
	global $CFG;
	$db = new PDO($CFG->connect->db['dsn'], $CFG->connect->db['user'], $CFG->connect->db['password']);
	return $db;
}

function lcproxy_publish($queue, $message) {
	global $CFG;
	$stomp = new \FuseSource\Stomp\Stomp($CFG->connect->stomp);
	try {
	    $stomp->connect();
	} catch (\FuseSource\Stomp\Exception\StompException $e) {
	    die("Couldnt connect to STOMP! " . $e);
	}
	$stomp->send($queue, $message);
	$stomp->disconnect();
}

/**
 * Is this user allowed to grab a list of courses?
 */
function lcproxy_canGetCourses() {
	global $DB;

	$sitecontext = get_context_instance(CONTEXT_SYSTEM);
	$site = get_site();

	$cats = $DB->get_records('course_categories');

	// Check permissions
	$cat_permissions = array();
	foreach ($cats as $cat) {
	  $context = get_context_instance(CONTEXT_COURSECAT, $cat->id);

	  if(has_capability('moodle/category:manage', $context)) {
	    array_push($cat_permissions, $cat->id);
	    break;
	  }
	}

	return count($cat_permissions) > 0;
}

/**
 * Prints a JSON list of all courses
 */
function lcproxy_printCourses() {

	$pdo = lcproxy_getDBConnection();

	$data = array();

	// Blame Patrick for this, ignore what the git blame says. - Sky
	$query = <<<SQL
	  SELECT 
	    c1.chksum,
	    CONCAT('[',COALESCE(GROUP_CONCAT(CONCAT('"',statecode.state,'"')),''),']') state,
	    c1.module_code,
	    c1.module_title,
	    c1.module_version,
	    c1.campus,
	    c1.campus_desc,
	    c1.synopsis,
	    c1.module_week_beginning,
	    c1.module_length,
	    c1.moodle_id,
	    c1.sink_deleted,
	    c1.student_count,
	    c1.teacher_count,
	    c1.convenor_count,
	    c1.parent_id,
	    c1.session_code,
	    c1.category_id,
	    c1.delivery_department,
	    CONCAT('[',COALESCE(GROUP_CONCAT(CONCAT('"',c2.chksum,'"')),''),']') children
	  FROM
	    courses c1
	      LEFT OUTER JOIN
	    courses c2 ON c1.module_delivery_key = c2.parent_id
	  LEFT OUTER JOIN (SELECT 'unprocessed' state, 1 code
	  UNION
	  SELECT 'scheduled' state, 2 code
	  UNION
	  SELECT 'processing' state, 4 code
	  UNION
	  SELECT 'created_in_moodle' state, 8 code
	  UNION
	  SELECT 'failed_in_moodle' state, 16 code
	  UNION
	  SELECT 'disengage' state, 32 code
	  UNION
	  SELECT 'disengaged_from_moodle' state, 64 code) statecode
	  ON (c1.state & statecode.code) > 0
SQL;
	// End Patrick blame (unless he has left, then it's all his fault)

	// Add the category restrictions if there are any
	if (isset($_GET['category_restrictions'])) {
	  $inQuery = implode(',', array_fill(0, count($_GET['category_restrictions']), '?'));
	  $query .= " WHERE c1.category_id IN ({$inQuery})";
	}

	$query .= ' GROUP BY c1.chksum';

	$q = $pdo->prepare($query);

	if (isset($_GET['category_restrictions'])) {
	  foreach ($_GET['category_restrictions'] as $k => $id) {
	    $q->bindValue(($k+1), $id);
	  }
	}
	// Done!

	$q->execute();

	$result = $q->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as $obj) {
	  if (!empty($obj['children'])) {
	    $obj['children'] = json_decode($obj['children']);
	  }
	  if (!empty($obj['state'])) {
	    $obj['state'] = json_decode($obj['state']);
	  }
	  $data[] = $obj;
	}

	echo json_encode($data);
}

/**
 * Get a course
 */
function lcproxy_getCourse($pdo, $courseid) {
	$stmt = $pdo->prepare("SELECT * FROM courses WHERE chksum=?");
	$stmt->execute(array($courseid));
	return $stmt->fetchObject();
}

/**
 * Is a given ID unique?
 */
function lcproxy_isCourseUnique($pdo, $session_code, $module_code, $chksum) {
	$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM courses WHERE session_code = ? AND module_code = ? AND chksum != ? AND state IN (2, 4, 6, 8, 10, 12)");
	$stmt->execute(array($session_code, $module_code, $chksum));
	$obj = $stmt->fetchObject();
	return $obj->count == 0;
}

/**
 * Update a course
 */
function lcproxy_updateForSchedule($pdo, $chksum, $mcode, $mtitle, $synopsis, $catid) {
	$stmt = $pdo->prepare("UPDATE courses SET chksum = ?, module_code = ?, module_title = ?, synopsis = ?, category_id = ?, state = 2 WHERE chksum = ?");
	$stmt->execute(array($chksum, $mcode, $mtitle, $synopsis, $catid, $chksum));
}

/**
 * Schedule courses
 */
function lcproxy_scheduleCourses() {
	$json = json_decode(file_get_contents("php://input"));
	$courses = $json->courses;

	if (empty($courses) || count($courses) > 200) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 422 Invalid number of courses' . $contents, true, 422);
		die("Invalid number of courses");
	}

	$pdo = lcproxy_getDBConnection();

	$result = array();
	foreach ($courses as $in_course) {
		$course = lcproxy_getCourse($pdo, $in_course->id);

		// Are we scheduled?
		if (in_array($course->state, array(2, 4, 6, 8, 10, 12))) {
			// Cannot continue with this one
			continue;
		}

		$course->module_code = $in_course->code;
		$course->module_title = $in_course->title;
		$course->synopsis = $in_course->synopsis;
		$course->category_id = $in_course->category;

		if ($course->category_id == 0) {
			// Cannot continue with this one
			$result[] = array("error_code" => "category_is_zero", "id" => $course->chksum);
			continue;
		}

		if (!lcproxy_isCourseUnique($pdo, $course->session_code, $course->module_code, $course->chksum)) {
			// Cannot continue with this one
			$result[] = array("error_code" => "duplicate", "id" => $course->chksum);
			continue;
		}

		lcproxy_updateForSchedule($pdo, $course->chksum, $course->module_code, $course->module_title, $course->synopsis, $course->category_id);

		lcproxy_publish('/queue/connect.job.create_course', $course->chksum);
	}

	if (count($result) > 0) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 422 error', true, 422);
	}

	echo json_encode($result);
}