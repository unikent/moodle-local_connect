<?php 

global $PAGE, $OUTPUT, $CFG, $SHAREDB;

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/sharedreport.php');

admin_externalpage_setup('reportconnectsharedreport', '', null, '', array('pagelayout' => 'report'));

// Dont show anything if there is nothing to show!
if (!\local_connect\utils::enable_sharedb()) {
	print_error("Shared Moodle has not been enabled on this system, so there is nothing to show!");
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("sharedreport", "local_connect"));
echo $OUTPUT->box_start('reportbox');

$table = new \html_table();
$table->head = array("Environment", "Distribution", "Course ID", "Shortname", "Fullname");
$table->attributes = array('class' => 'admintable generaltable');
$table->data = array();

// Grab a list of courses we can see.
$records = $SHAREDB->get_records('course_list');
foreach ($records as $record) {
	$table->data[] = new \html_table_row(array(
		$record->moodle_env,
		$record->moodle_dist,
		$record->moodle_id,
		$record->shortname,
		$record->fullname,
	));
}

echo \html_writer::table($table);

// Allow admins to regenerate list.
if (has_capability('moodle/site:config', \context_system::instance())) {
	echo '<a href="'.$CFG->wwwroot.'/local/connect/regenerate.php">Regenerate list? (Warning: Do not do this unless you know exactly what it means.)</a>';
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
