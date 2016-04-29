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
 * Local stuff for Moodle Connect
 *
 * @package    local_connect
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/accesslib.php');

require_login();

if (!\local_connect\util\helpers::is_enabled()) {
    print_error('connect_disabled', 'local_connect');
}

$courses = get_user_capability_course('moodle/course:update');
if (empty($courses)) {
    print_error('accessdenied', 'local_connect');
}

// Page setup.
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/manage/index.php');
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add('Connect Administration');

echo $OUTPUT->header();
echo $OUTPUT->heading('Connect Administration');

echo \html_writer::tag('p', 'You currently manage the following courses:');

// We have a list of courses we can manage.
// We want to manage SDS deliveries to those courses.
echo \html_writer::start_tag('ul');
foreach ($courses as $obj) {
    $course = $DB->get_record('course', array('id' => $obj->id));

    $a = \html_writer::tag('a', $course->shortname . " - " . $course->fullname, array(
        'href' => $CFG->wwwroot . '/local/connect/manage/course.php?mid=' . $course->id
    ));
    echo \html_writer::tag('li', $a);
}
echo \html_writer::end_tag('ul');

echo $OUTPUT->footer();