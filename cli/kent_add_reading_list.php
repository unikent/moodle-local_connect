<?php 

define('CLI_SCRIPT', 1);
require_once('../../config.php');
require_once('../../course/lib.php');
require_once('../../mod/aspirelists/lib.php');

global $CFG, $DB;

echo "\nAdding aspire - Looking up all modules \n";

$sql = "SELECT c.* FROM {$CFG->prefix}course as c
		INNER JOIN {$CFG->prefix}connect_course_dets as cd ON cd.course = c.id 
		WHERE c.category > 0 AND cd.campus = 'Canterbury'";

$courses = $DB->get_records_sql($sql);
$module = $DB->get_record('modules', array('name'=>'aspirelists'));

$count = 0;
foreach($courses as $c) {
	
	$count++;
	echo "Adding aspire - adding reading list for {$c->shortname}\n";

	$rl = new stdClass();
	$rl->course 		= $c->id;
	$rl->name 			= 'Reading list';
	$rl->intro 			= '';
	$rl->introformat 	= 1;
	$rl->category 		= 'all';
	$rl->timemodified	= time();

	$instance = aspirelists_add_instance($rl, new stdClass());

	$sql = "SELECT id, sequence FROM {$CFG->prefix}course_sections 
			WHERE course = {$c->id} 
				AND section = 0";

	$section = $DB->get_record_sql($sql);

	$cm = new stdClass();
	$cm->course 		= $c->id;
	$cm->module 		= $module->id;
	$cm->instance 		= $instance;
	$cm->section 		= $section->id;
	$cm->visible 		= 1;

	$cm->coursemodule = add_course_module($cm);

	$sequence = "$cm->coursemodule,$section->sequence";

	$DB->set_field('course_sections', 'sequence', $sequence, array('id'=>$section->id));

	// $DB->set_field('course_modules', 'section', $sectionid, array('id'=>$cm->coursemodule));

	$eventdata = new stdClass();
    $eventdata->modulename = $module->name;
    $eventdata->name       = $rl->name;
    $eventdata->cmid       = $cm->coursemodule;
    $eventdata->courseid   = $c->id;
    $eventdata->userid     = 2;
    events_trigger('mod_created', $eventdata);

    rebuild_course_cache($c->id);
}

echo "Adding aspire - Added reading list to {$count} modules\n";

echo "Adding aspire - finished! Have a nice day :)\n";

