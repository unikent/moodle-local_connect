<?php
/**
 * Prototype Rollover Page
 */

require(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/local/connect/rollover_form.php');

global $PAGE, $OUTPUT, $USER, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/rollover.php');
$title = get_string('rollover_heading', 'local_connect');
$PAGE->set_title($title);
$PAGE->set_pagelayout('admin');

if (!\local_connect\utils::enable_new_features()) {
	print_error('new_feature_disabled', 'local_connect');
}

$PAGE->requires->js_init_call('M.local_rollover.init', array(), false, array(
    'name' => 'local_enrolment',
    'fullpath' => '/local/connect/scripts/rollover.js',
    'requires' => array("autocomplete")
));

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

echo $OUTPUT->box_start('noticebox', 'notice');
echo html_writer::tag('b', 'Warning! These pages are currently in BETA and may not be stable.');
echo $OUTPUT->box_end();

$rolloverform = new connect_rollover_form(null, array());
if ($rolloverdata = $rolloverform->get_data()) {
}
$rolloverform->display();

echo $OUTPUT->footer();