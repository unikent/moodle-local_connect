<?php 

define('CLI_SCRIPT', 1);

die("You probably don't want to run this...");

require_once('../../config.php');
require_once('../../user/lib.php');

global $CFG, $USER, $DB;

require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/group/lib.php');

$courses = $DB->get_recordset('course');

echo "\nGetting Module reset functions ..... \n\n";

$unsupported_mods = array();
$data = new stdClass;
$allmods = $DB->get_records('modules');

if(isset($allmods)) {
    foreach ($allmods as $mod) {
        if($mod->name === 'turnitintool') {
            continue;
        }
        $modname = $mod->name;
        $modfile = $CFG->dirroot . "/mod/$modname/lib.php";
        $mod_data_opt = $modname.'_reset_course_form_defaults';
        if(file_exists($modfile)) {
            include_once($modfile);
            if(function_exists($mod_data_opt)) {
                var_dump($mod_data_opt());
                foreach ($mod_data_opt() as $k => $v) {
                    $data->$k = 1;
                }
            } else if(!function_exists($mod_data_opt)) {
                $unsupported_mods[] = $modname;
            }
        } else {
            echo 'Missing lib.php in ' . $modname . " module\n";
        }
    }

    echo "The following modules do not support reset: \n" . implode(', ', $unsupported_mods) . "\n\n";
}

echo "Starting course resets \n\n";

foreach($courses as $c) {

    $data->courseid = $c->id;

    echo "Reseting " . $c->shortname . "\n";
	
	$context = context_course::instance($c->id);


	$DB->delete_records('log', array('course'=>$c->id));
    echo ".";

    $DB->delete_records('event', array('courseid'=>$c->id));
    echo ".";

    require_once($CFG->dirroot.'/notes/lib.php');
    note_delete_all($c->id);
    echo ".";

    require_once($CFG->dirroot.'/blog/lib.php');
    blog_remove_associations_for_course($c->id);
    echo ".";

    // Delete course and activity completion information.
    $course = $DB->get_record('course', array('id'=>$c->id));
    $cc = new completion_info($course);
    $cc->delete_all_completion_data();   
    echo ".";

    $children = get_child_contexts($context);
    foreach ($children as $child) {
    	$DB->delete_records('role_capabilities', array('contextid'=>$child->id));
	}
    echo ".";

    $DB->delete_records('role_capabilities', array('contextid'=>$context->id));
    echo ".";

    foreach ($children as $child) {
        role_unassign_all(array('contextid'=>$child->id));
    }
    echo ".";

    //force refresh for logged in users
    mark_context_dirty($context->path);

    // First unenrol users - this cleans some of related user data too, such as forum subscriptions, tracking, etc.
    $unenrolled = array();
    $plugins = enrol_get_plugins(true);
    $instances = enrol_get_instances($c->id, true);
    foreach ($instances as $key=>$instance) {
        if (!isset($plugins[$instance->enrol])) {
            unset($instances[$key]);
            continue;
        }
        if (!$plugins[$instance->enrol]->allow_unenrol($instance)) {
            unset($instances[$key]);
        }
    }

    $sqlempty = $DB->sql_empty();

    $sql = "SELECT DISTINCT ue.userid, ue.enrolid
              FROM {user_enrolments} ue
              JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
              JOIN {context} c ON (c.contextlevel = :courselevel AND c.instanceid = e.courseid)
              JOIN {role_assignments} ra ON (ra.contextid = c.id AND ra.userid = ue.userid)";
    $params = array('courseid'=>$c->id, 'courselevel'=>CONTEXT_COURSE);

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $ue) {
        if (!isset($instances[$ue->enrolid])) {
            continue;
        }
        $plugins[$instances[$ue->enrolid]->enrol]->unenrol_user($instances[$ue->enrolid], $ue->userid);
        $unenrolled[$ue->userid] = $ue->userid;
    }
    echo ".";

 //    if (!empty($unenrolled)) {
 //        echo 'Unenrolled ' . count($unenrolled) . ' users';
 //    }

    // remove all group members
    groups_delete_group_members($c->id);
    echo ".";

    // remove all groups
    groups_delete_groups($c->id, false);
    echo ".";

    // remove all grouping members
    groups_delete_groupings_groups($c->id, false);
    echo ".";

    // remove all groupings
    groups_delete_groupings($c->id, false);
    echo ".";



    // Look in every instance of every module for data to delete
    if(isset($allmods)) {

        foreach ($allmods as $mod) {

            if($mod->name === 'turnitintool') {
                continue;
            }

            $modname = $mod->name;
            $moddeleteuserdata = $modname.'_reset_userdata';   // Function to delete user data
            if (!$DB->count_records($modname, array('course'=>$c->id))) {
                continue; // Skip mods with no instances
            }
            if (function_exists($moddeleteuserdata)) {
                $modstatus = $moddeleteuserdata($data);
                if (is_array($modstatus)) {
                    echo ".";
                } else {
                    echo "\nModule" . $modname. "returned incorrect status - must be an array! \n";
                }
            } 
        }
    }

    // reset gradebook
    remove_course_grades($c->id, false);
    grade_grab_course_grades($c->id);
    grade_regrade_final_grades($c->id);
    echo ".";

    // reset comments
    require_once($CFG->dirroot.'/comment/lib.php');
    comment::reset_course_page_comments($context);
    echo ".\n";
}

echo "Removing turnitintool modules\n";
$tt = $DB->get_record('modules', array('name' => 'turnitintool'));
$DB->delete_records('turnitintool');
$DB->delete_records('turnitintool_comments');
$DB->delete_records('turnitintool_courses');
$DB->delete_records('turnitintool_parts');
$DB->delete_records('turnitintool_submissions');
$DB->delete_records('turnitintool_users');
$DB->delete_records('course_modules', array('module'=> $tt->id));
$DB->set_field('course', 'modinfo', '');

$sql = "select distinct u.id, u.username from {role_assignments} ra
        inner join {context} c on c.id = ra.contextid
        inner join {user} u on u.id = ra.userid 
        where c.contextlevel != 40";

$sql = "select distinct u.id, u.username
from {user_enrolments} ue
inner join {enrol} e on ue.enrolid = e.id
inner join {user} u on ue.userid = u.id
where e.enrol != 'category'";

$users = $DB->get_records_sql($sql);

echo "\nDeleting users with enrollments other than category\n";

foreach ($users as $u) {
    $result = user_delete_user($u);
    echo ".";
}

$sql = "select distinct u.id, u.username
        from {user} u
        left join {user_enrolments} ue on ue.userid = u.id
        where ue.id is null 
        and deleted != 1
        and u.id > 2";
$users = $DB->get_records_sql($sql);

echo "\nDeleting users with no enrollments\n";

foreach ($users as $u) {
    $result = user_delete_user($u);
    echo ".";
}

echo "\nNote: users are still in db just set to deleted. \n";
echo "To delete these users from the db, just remove all \n";
echo "user db entries with deleted set to 1 \n";

echo "\nReset complete!\n";


