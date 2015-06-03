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

if (!\local_connect\util\helpers::is_enabled()) {
    print_error('connect_disabled', 'local_connect');
}

$mid = required_param('mid', PARAM_INT);
$course = $DB->get_record('course', array('id' => $mid), '*', MUST_EXIST);
$ctx = context_course::instance($course->id);

require_login($course->id);
require_capability('moodle/course:update', $ctx);

$PAGE->set_context($ctx);
$PAGE->set_title("SDS Links");
$PAGE->set_url(new \moodle_url('/local/connect/manage/course.php', array(
    'mid' => $mid
)));
$PAGE->set_pagelayout('admin');
$PAGE->requires->css('/local/connect/styles/styles.min.css');

echo $OUTPUT->header();
echo $OUTPUT->heading("SDS Links");

echo \html_writer::tag('p', "{$course->shortname} recieves data from the following SDS modules:");

echo \html_writer::start_tag('ul');
$links = \local_connect\course::get_by('mid', $course->id, true);
foreach ($links as $obj) {
    echo \html_writer::tag('li', \html_writer::tag('a', "$obj->module_code - $obj->module_title", array(
        'href' => new \moodle_url('/local/connect/browse/course.php', array(
            'id' => $obj->id
        )),
        'target' => 'blank'
    )));
}
echo \html_writer::end_tag('ul');

echo $OUTPUT->single_button(new \moodle_url('/local/connect/manage/addlink.php', array(
    'mid' => $mid
)), 'Add a link');

echo $OUTPUT->footer();