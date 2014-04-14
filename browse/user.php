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
 * Browse data for a user.
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require (dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/tablelib.php');

/**
 * Page setup.
 */
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/browse/user.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('connectbrowse', 'local_connect'));
$PAGE->navbar->add("Connect Browser", new moodle_url('/local/connect/browse/'));
$PAGE->navbar->add("User View");

/**
 * Check capabilities.
 */
require_capability("local/helpdesk:access", context_system::instance());

/**
 * And, the actual page.
 */

$userid = required_param("id", PARAM_INT);
$user = \local_connect\user::get($userid);

if ($user === null) {
	print_error("User does not exist!");
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connectbrowse_user', 'local_connect') . $user->login);

// The Groups Table
{
	echo $OUTPUT->heading("User Information", 2);

	$table = new flexible_table('group-info');
	$table->define_columns(array('variable', 'value'));
	$table->define_headers(array("Variable", "Value"));
	$table->define_baseurl($CFG->wwwroot.'/local/connect/browse/user.php');
	$table->setup();

	$mid = "-";
	if (!empty($user->mid)) {
		$mid = \html_writer::link($user->get_moodle_url(), $user->mid);
	}

	$table->add_data(array("id", $user->id));
	$table->add_data(array("mid", $mid));
	$table->add_data(array("ukc", $user->ukc));
	$table->add_data(array("login", $user->login));
	$table->add_data(array("title", $user->title));
	$table->add_data(array("initials", $user->initials));
	$table->add_data(array("family_name", $user->family_name));

	$table->print_html();
}

// The Enrolments Table
{
	echo $OUTPUT->heading("Enrolments", 2);

	$table = new flexible_table('user-enrolments');
	$table->define_columns(array('course', 'role', 'in_moodle', 'action'));
	$table->define_headers(array("Course", "Role", "In Moodle?", "Action"));
	$table->define_baseurl($CFG->wwwroot.'/local/connect/browse/user.php');
	$table->setup();

	foreach ($user->enrolments as $enrolment) {
		$url = new \moodle_url("/local/connect/browse/course.php", array("id" => $enrolment->courseid));
		$course = \html_writer::link($url->out(true), $enrolment->course->module_code);

		$push_url = new \moodle_url("/local/connect/browse/sync/enrolment.php", array("id" => $enrolment->id));
		$push_link = \html_writer::link($push_url->out(false), "Push");

		$in_moodle = $enrolment->is_in_moodle();
		$table->add_data(array($course, $enrolment->role->name, $in_moodle ? "Yes" : "No", $in_moodle ? '' : $push_link));
	}

	$push_url = new \moodle_url("/local/connect/browse/sync/user.php", array("id" => $user->id));
	$push_link = \html_writer::link($push_url->out(false), "Push All");
	$table->add_data(array('', '', '', $push_link));

	$table->print_html();
}

echo $OUTPUT->footer();