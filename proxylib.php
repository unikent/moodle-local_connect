<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A library for new-style proxy functions
 *
 * @package    local
 * @subpackage connect
 * @copyright  2013 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/stomp/Stomp.php');

/**
 * Proxy functions
 */
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
 * Prints a JSON list of all courses
 */
function lcproxy_getCourses() {
    // Set up our various variables
    $cache = cache::make('local_connect', 'kent_connect');
    $pdo = connect_db();
    $data = array();

    // Cache in MUC
    $cache_content = $cache->get('lcproxy_getCourses');
    if ($cache_content !== false) {
        return $cache_content;
    }

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

    $cache->set('lcproxy_getCourses', $data);

    return $data;
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

    $pdo = connect_db();

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