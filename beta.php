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

require_once(dirname(__FILE__) . '/../../config.php');

if (!\local_connect\util\helpers::is_enabled()) {
    print_error('connect_disabled', 'local_connect');
}

require_login();

// Page setup.
$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/local/connect/beta.php');
$PAGE->set_title('SDS push tool (beta)');
$PAGE->set_pagelayout('fullwidth');
$PAGE->requires->js_call_amd('local_connect/beta', 'init', array());
$PAGE->requires->css('/local/connect/less/build/build.css');

echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->title);

$link = \html_writer::link(new \moodle_url('/local/connect/index.php', array('nobeta' => true)), 'Go back', array('class' => 'alert-link'));
echo \html_writer::div('<i class="fa fa-info-circle"></i> You have been enrolled on the beta program. ' . $link . '.', 'alert alert-info');

echo \html_writer::start_div('beta');

$connect = new \local_connect\core();
$courses = $connect->get_my_courses();
$courses = array_filter($courses, function($course) {
    return !$course->is_in_moodle();
});
$renderer = $PAGE->get_renderer('local_connect');
$renderer->render_beta($courses);

echo \html_writer::end_div('beta');
echo $OUTPUT->footer();
