<?php
/**
 * Attempts to fix user enrolments
 */

define('AJAX_SCRIPT', true);

require(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $PAGE, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/util/ajax-enrolment.php');

require_login();

$response = array("result" => "Not Implemented");

header('Content-Type: application/json; charset: utf-8');
echo json_encode($response);