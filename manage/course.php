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
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

if (!\local_connect\util\helpers::is_enabled()) {
    print_error('connect_disabled', 'local_connect');
}

$mid = required_param('mid', PARAM_INT);
$course = $DB->get_record('course', array('id' => $mid), '*', MUST_EXIST);
$ctx = context_course::instance($course->id);

require_login($course->id);
require_capability('moodle/course:update', $ctx);

$PAGE->set_context($ctx);
$PAGE->set_title('SDS Links');
$PAGE->set_url(new \moodle_url('/local/connect/manage/course.php', array(
    'mid' => $mid
)));
$PAGE->set_pagelayout('admin');
$PAGE->requires->css('/local/connect/styles/styles.min.css');

echo $OUTPUT->header();
echo $OUTPUT->heading('SDS Links');

echo \html_writer::tag('p', "{$course->shortname} recieves data from the following SDS modules:");

echo \html_writer::start_div('panel-group', array(
    'id' => 'linksaccordion',
    'role' => 'tablist',
    'aria-multiselectable' => 'true'
));

$links = \local_connect\course::get_by('mid', $course->id, true);
foreach ($links as $obj) {
    echo \html_writer::start_div('panel panel-default');
    echo \html_writer::start_div('panel-heading', array(
        'id' => "heading{$obj->id}"
    ));
    echo \html_writer::link("#collapse{$obj->id}", "{$obj->module_code} - {$obj->module_title}&nbsp;", array(
        'data-toggle' => 'collapse',
        'data-parent' => '#linksaccordion',
        'aria-expanded' => 'true',
        'aria-controls' => "collapse{$obj->id}"
    ));
    echo \html_writer::tag('a', '<i class="fa fa-unlink"></i>', array(
        'title' => 'Unlink',
        'href' => new \moodle_url('/local/connect/manage/unlink.php', array(
            'id' => $obj->id,
            'sesskey' => sesskey()
        )),
        'target' => 'blank',
        'style' => 'float: right;'
    ));
    echo \html_writer::end_div();

    echo \html_writer::start_div('panel-collapse collapse', array(
        'id' => "collapse{$obj->id}",
        'role' => 'tabpanel',
        'aria-labelledby' => "heading{$obj->id}"
    ));
    $table = $obj->get_flexible_table($PAGE->url);
    $table->print_html();
    echo \html_writer::end_div();

    echo \html_writer::end_div();
}

echo \html_writer::end_div();

echo $OUTPUT->single_button(new \moodle_url('/local/connect/manage/addlink.php', array(
    'mid' => $mid
)), 'Add a link');

echo $OUTPUT->footer();