<?php
/**
 * Attempts to fix user enrolments
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib/connectlib.php');

global $PAGE, $OUTPUT, $USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/util/ajax-enrolment.php');

require_login();

$response = array(
	"result" => ""
);

$courses = connect_get_user_courses($USER->username);
$courses = array_map("connect_translate_enrolment", $courses);
$courses = array_filter($courses, "connect_filter_enrolment");
$courses = array_filter($courses, "connect_check_enrolment");

if (empty($courses)) {
	$response["result"] = "No missing enrolments found!<br />Please contact helpdesk if you are missing any modules.";
}

foreach ($courses as $course) {
	if (connect_send_enrolment($course)) {
		$response["result"] .= "Enrolled on course " . $course['module_title'] . ".<br/>";
	} else {
		$response["result"] .= "Failed to enrol on course " . $course['module_title'] . ". Please contact helpdesk to gain access to this module.<br/>";
	}
}

header('Content-Type: application/json; charset: utf-8');
echo json_encode($response);