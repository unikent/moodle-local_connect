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
 * Creates a given course's enrolments
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require (dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$courseid = required_param("id", PARAM_INT);
$course = \local_connect\course::get($courseid);

/**
 * Page setup.
 */
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/browse/sync/course.php');
$PAGE->set_title(get_string('connectbrowse_push', 'local_connect'));

admin_externalpage_setup('connectdatabrowse', '', null, '', array('pagelayout' => 'report'));

$PAGE->navbar->add("Connect Course", new moodle_url('/local/connect/browse/course.php', array("id" => $course->id)));
$PAGE->navbar->add("Pushing...");

if ($course === null) {
	print_error("Course does not exist!");
}

/**
 * And, the actual page.
 */
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connectbrowse_push', 'local_connect'));

if (!$course->is_in_moodle()) {
	print_error("Course must exist before you push the enrolments!");
}

foreach ($course->enrolments as $enrolment) {
	// Can also create the user.
	if (!$enrolment->user->is_in_moodle()) {
		echo "Creating user {$enrolment->user->login}...";
		if ($enrolment->user->create_in_moodle()) {
			echo "done!<br />";
		} else {
			echo "error!<br />";
			continue;
		}
	}

	// Enrol.
	if (!$enrolment->is_in_moodle()) {
		echo "Enrolling user {$enrolment->user->login}...";
		if ($enrolment->create_in_moodle()) {
			echo "done!<br />";
		} else {
			echo "error!<br />";
		}
	}
}

echo "Done!<br/>";

echo $OUTPUT->footer();