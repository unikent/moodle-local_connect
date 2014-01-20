<?php
/**
 * Grabs rollover sources for this installation
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');

global $CFG, $PAGE, $OUTPUT, $USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/ajax/rollover_sources.php');

if (!isloggedin()) {
	throw new moodleexception("You must be logged in.");
}

if (!\local_connect\utils::is_enabled() || !\local_connect\utils::enable_new_features()) {
	throw new moodleexception("This feature has not been enabled.");
}

$name = required_param('name', PARAM_ALPHANUMEXT);
$source = optional_param('source', $CFG->kent->distribution, PARAM_ALPHANUMEXT);

$data = \local_connect\rollover::get_course_list($source, $name);

$json_data = array();
foreach ($data as $datum) {
	$json_data[] = $datum->shortname;
}

echo json_encode(array_unique($json_data));