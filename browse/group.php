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
 * Browse data for a group.
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require (dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/tablelib.php');

$groupid = required_param("id", PARAM_INT);
$group = \local_connect\group::get($groupid);

/**
 * Page setup.
 */
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/browse/group.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('connectbrowse_group', 'local_connect'));

if ($group === null) {
	print_error("Group does not exist!");
}

$PAGE->navbar->add("Connect Browser", new moodle_url('/local/connect/browse/group.php'));
$PAGE->navbar->add("Connect Course", new moodle_url('/local/connect/browse/course.php', array("id" => $group->courseid)));
$PAGE->navbar->add("Connect Group");

/**
 * Check capabilities.
 */
if (!has_capability('moodle/site:config', context_system::instance())) {
    print_error('accessdenied', 'admin');
}

/**
 * And, the actual page.
 */
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connectbrowse_group', 'local_connect') . $group->name);

// The Groups Table
{
	echo $OUTPUT->heading("Group Information", 2);

	$table = new flexible_table('group-info');
	$table->define_columns(array('variable', 'value'));
	$table->define_headers(array("Variable", "Value"));
	$table->define_baseurl($CFG->wwwroot.'/local/connect/browse/group.php');
	$table->setup();

	$mid = "-";
	if (!empty($group->mid)) {
		$mid = \html_writer::link($group->get_moodle_url(), $group->mid);
	}

	$courseurl = new \moodle_url("/local/connect/browse/course.php", array("id" => $group->courseid));
	$courseid = \html_writer::link($courseurl->out(true), $group->courseid);

	$table->add_data(array("id", $group->id));
	$table->add_data(array("mid", $mid));
	$table->add_data(array("courseid", $courseid));
	$table->add_data(array("name", $group->name));

	// Enrolment counts.
	$table->add_data(array("staff_enrolled", $group->count_staff()));
	$table->add_data(array("students_enrolled", $group->count_students()));

	$table->print_html();
}

// The Enrolments Table
{
	echo $OUTPUT->heading("Enrolments", 2);

	$table = new flexible_table('group-enrolments');
	$table->define_columns(array('username', 'in_moodle'));
	$table->define_headers(array("Username", "In Moodle?"));
	$table->define_baseurl($CFG->wwwroot.'/local/connect/browse/group.php');
	$table->setup();

	foreach ($group->enrolments as $enrolment) {
		$table->add_data(array($enrolment->user->login, $enrolment->is_in_moodle() ? "Yes" : "No"));
	}

	$table->print_html();
}

echo $OUTPUT->footer();