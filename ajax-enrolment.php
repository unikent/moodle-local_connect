<?php
/**
 * Attempts to fix user enrolments
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../config.php');

global $PAGE, $OUTPUT, $USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/util/ajax-enrolment.php');

require_login();

$response = array(
	"result" => ""
);

$enrolments = \local_connect\enrolment::get_courses($USER->username);
foreach ($enrolments as $enrolment) {
	if (!$enrolment->is_in_moodle()) {
		if ($enrolment->create_in_moodle()) {
			$response["result"] .= "Enrolled on course $enrolment.<br/>";
		} else {
			$response["result"] .= "Failed to enrol on course $enrolment. Please contact helpdesk to gain access to this module.<br/>";
		}
	}
}

if (empty($response["result"])) {
	$response["result"] = "No missing enrolments found!<br />Please contact helpdesk if you are missing any modules.";
}

header('Content-Type: application/json; charset: utf-8');
echo json_encode($response);