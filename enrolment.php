<?php
/**
 * This page is an AJAX placeholder for users who wish to
 * fix their enrolments.
 */

require(dirname(__FILE__) . '/../../config.php');
require_once('locallib.php');

global $PAGE, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/enrolment.php');

$PAGE->set_title("Enrolment");
$PAGE->set_heading("Enrolment");

if (!\local_connect\utils::enable_new_features()) {
	print_error('new_feature_disabled', 'local_connect');
}

$PAGE->requires->js_init_call('M.local_enrolment.init', array(), false, array(
    'name' => 'local_enrolment',
    'fullpath' => '/local/connect/scripts/enrolments.js',
    'requires' => array("node", "io", "dump", "json-parse")
));

require_login();

if (!\local_connect\utils::is_enabled()) {
	print_error('connect_disabled', 'local_connect');
}

echo $OUTPUT->header();

echo $OUTPUT->heading("Fixing your enrolments");

echo $OUTPUT->box_start('enrolmentbox');
	echo '<p>Please wait whilst we check your enrolments...</p>';
	echo $OUTPUT->pix_icon('i/loading_small', "Fixing your enrolments...");
echo $OUTPUT->box_end();

echo $OUTPUT->footer();