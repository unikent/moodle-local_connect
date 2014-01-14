<?php
/**
 * Attempts to fix user enrolments
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../config.php');

global $PAGE, $OUTPUT, $USER;

if (!\local_connect\utils::enable_new_features()) {
	throw new \moodle_exception(get_string('new_feature_disabled', 'local_connect'));
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/util/ajax-enrolment.php');

require_login();

$response = array(
	"result" => ""
);

$enrolments = \local_connect\enrolment::get_enrolments_for_user($USER->username);
foreach ($enrolments as $enrolment) {
	if (!$enrolment->is_in_moodle()) {
		if ($enrolment->create_in_moodle()) {
			$response["result"] .= "Enrolled on course $enrolment.<br/>";
		} else {
			$response["result"] .= "Failed to enrol on course $enrolment. Please contact <a href=\"mailto:helpdesk@kent.ac.uk\">helpdesk</a> to gain access to this module.<br/>";
		}
	}
}

if (empty($response["result"])) {
	$response["result"] = "No missing enrolments found!<br />Please contact <a href=\"mailto:helpdesk@kent.ac.uk\">helpdesk</a> if you are missing any modules.";
}

header('Content-Type: application/json; charset: utf-8');
echo json_encode($response);