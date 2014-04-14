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
 * Creates a given user's enrolments
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require (dirname(__FILE__) . '/../../../../config.php');

$userid = required_param("id", PARAM_INT);
$user = \local_connect\user::get($userid);

/**
 * Page setup.
 */
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/browse/sync/user.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('connectbrowse_push', 'local_connect'));

require_capability("local/helpdesk:access", context_system::instance());

if ($user === null) {
	print_error("User does not exist!");
}

$PAGE->navbar->add("Connect Browser", new moodle_url('/local/connect/browse/index.php'));
$PAGE->navbar->add("Connect User", new moodle_url('/local/connect/browse/user.php', array("id" => $user->id)));
$PAGE->navbar->add("Pushing...");

/**
 * And, the actual page.
 */
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connectbrowse_push', 'local_connect'));

// We can create the user if needs be.
if (!$user->is_in_moodle()) {
	if ($user->create_in_moodle()) {
		echo "Creating user... done!<br />";
	} else {
		print_error("Error creating user!");
	}
}

// And the enrolments..
foreach ($user->enrolments as $enrolment) {
	// If the course doesnt exist, skip it!
	if (!$enrolment->course->is_in_moodle()) {
		echo "Skipping {$enrolment->course->module_code} as it does not exist in Moodle.<br />";
		continue;
	}

	// Create it in Moodle!
	if (!$enrolment->is_in_moodle()) {
		echo "Enrolling user in course {$enrolment->course->module_code}...";
		if ($enrolment->create_in_moodle()) {
			echo "done!<br />";
		} else {
			echo "error!<br />";
		}
	}
}

echo "Done!<br/>";

echo $OUTPUT->footer();