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
 * Creates a given group's enrolments
 *
 * @package    local_connect
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require (dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$groupid = required_param("id", PARAM_INT);
$group = \local_connect\group::get($groupid);

/**
 * Page setup.
 */
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/browse/sync/group.php');
$PAGE->set_title(get_string('connectbrowse_push', 'local_connect'));

admin_externalpage_setup('connectdatabrowse', '', null, '', array('pagelayout' => 'report'));

$PAGE->navbar->add("Connect Course", new moodle_url('/local/connect/browse/course.php', array("id" => $group->courseid)));
$PAGE->navbar->add("Connect Group", new moodle_url('/local/connect/browse/group.php', array("id" => $group->id)));
$PAGE->navbar->add("Pushing...");

if ($group === null) {
	print_error("Group does not exist!");
}

/**
 * And, the actual page.
 */
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connectbrowse_push', 'local_connect'));

if (!$group->course->is_in_moodle()) {
	print_error("Course must exist before you push the group enrolments!");
}

// We can create the group if needs be.
if (!$group->is_in_moodle()) {
	if ($group->create_in_moodle()) {
		echo "Creating group... done!<br />";
	} else {
		print_error("Error creating group!");
	}
}

// And the enrolments..
foreach ($group->enrolments as $ge) {
	// Can also create the user.
	if (!$ge->user->is_in_moodle()) {
		echo "Creating user {$ge->user->login}...";
		if ($ge->user->create_in_moodle()) {
			echo "done!<br />";
		} else {
			echo "error!<br />";
			continue;
		}
	}

	// Create it in Moodle!
	if (!$ge->is_in_moodle()) {
		echo "Enrolling user {$ge->user->login} in group...";
		if ($ge->create_in_moodle()) {
			echo "done!<br />";
		} else {
			echo "error!<br />";
		}
	}
}

echo "Done!<br/>";

echo $OUTPUT->footer();