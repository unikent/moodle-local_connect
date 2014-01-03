<?php
/**
 * This page is an AJAX placeholder for users who wish to
 * fix their enrolments.
 */

require(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $PAGE, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/util/enrolment.php');

$PAGE->set_title("Enrolment");
$PAGE->set_heading("Enrolment");

require_login();

echo $OUTPUT->header();

echo $OUTPUT->footer();