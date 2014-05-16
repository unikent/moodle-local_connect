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
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/accesslib.php');

require_login();

if (!\local_connect\util\helpers::is_enabled()) {
    print_error('connect_disabled', 'local_connect');
}

$mid = required_param('mid', PARAM_INT);
$course = $DB->get_record('course', array('id' => $mid), '*', MUST_EXIST);
$ctx = context_course::instance($course->id);

$PAGE->set_context($ctx);
$PAGE->set_url('/local/connect/manage/course.php');
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(\html_writer::tag('a', 'Connect Administration', array(
    'href' => '/local/connect/manage/index.php'
)));
$PAGE->navbar->add($course->shortname);

// Check we have the capabilities.
if (!has_capability('moodle/course:update', $ctx)) {
    print_error("Access denied");
}

echo $OUTPUT->header();
echo $OUTPUT->heading($course->shortname);

echo \html_writer::tag('p', 'This course recieves data from the following SDS modules:');

echo \html_writer::start_tag('ul');
$links = $DB->get_records('connect_course', array('mid' => $course->id));
foreach ($links as $obj) {
    $a = \html_writer::tag('a', $obj->module_delivery_key, array(
        'href' => $CFG->wwwroot . '/local/connect/browse/course.php?id=' . $obj->id,
        'target' => 'blank'
    ));
    echo \html_writer::tag('li', $a);
}
echo \html_writer::end_tag('ul');

echo $OUTPUT->footer();