<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

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
$PAGE->set_url('/local/connect/ajax/fix-enrolment.php');

require_login();

$response = array(
    "result" => ""
);

$enrolments = \local_connect\enrolment::get_my_enrolments();
foreach ($enrolments as $enrolment) {
    if (!$enrolment->is_in_moodle()) {
        if ($enrolment->create_in_moodle()) {
            $response["result"] .= "Enrolled on course $enrolment.<br/>";
        } else {
            $response["result"] .= "Failed to enrol on course $enrolment. Please contact ";
            $response["result"] .= \html_writer::tag('a', 'helpdesk', array(
                'href' => 'mailto:helpdesk@kent.ac.uk'
            ));
            $response["result"] .= " to gain access to this module.<br/>";
        }
    }
}

if (empty($response["result"])) {
    $response["result"] = "No missing enrolments found!<br />Please contact ";
    $response["result"] .= \html_writer::tag('a', 'helpdesk', array(
        'href' => 'mailto:helpdesk@kent.ac.uk'
    ));
    $response["result"] .= " if you are missing any modules.";
}

echo $OUTPUT->header();
echo json_encode($response);