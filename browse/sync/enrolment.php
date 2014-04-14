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
 * Creates a given enrolment
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require (dirname(__FILE__) . '/../../../../config.php');

$enrolmentid = required_param("id", PARAM_INT);
$enrolment = \local_connect\enrolment::get($enrolmentid);

/**
 * Page setup.
 */
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/browse/sync/enrolment.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('connectbrowse_push', 'local_connect'));

require_capability("local/helpdesk:access", context_system::instance());

if ($enrolment === null) {
	print_error("Enrolment does not exist!");
}

$PAGE->navbar->add("Connect Browser", new moodle_url('/local/connect/browse/index.php'));
$PAGE->navbar->add("Connect User", new moodle_url('/local/connect/browse/user.php', array("id" => $enrolment->userid)));
$PAGE->navbar->add("Connect Course", new moodle_url('/local/connect/browse/course.php', array("id" => $enrolment->courseid)));
$PAGE->navbar->add("Pushing...");

/**
 * And, the actual page.
 */
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connectbrowse_push', 'local_connect'));

if (!$enrolment->course->is_in_moodle()) {
	print_error("Course must exist before you push the enrolments!");
}

// Can also create the user.
if (!$enrolment->user->is_in_moodle()) {
	if ($enrolment->user->create_in_moodle()) {
		echo "Creating user... done!<br />";
	} else {
		print_error("Error creating user!");
	}
}

// Enrol.
if (!$enrolment->is_in_moodle()) {
	if ($enrolment->create_in_moodle()) {
		echo "Enrolling user... done!<br />";
	} else {
		print_error("Error enrolling user!");
	}
}

echo "Done!<br/>";

echo $OUTPUT->footer();