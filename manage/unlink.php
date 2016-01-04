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

if (!\local_connect\util\helpers::is_enabled()) {
    print_error('connect_disabled', 'local_connect');
}

$id = required_param('id', PARAM_INT);
$connect = \local_connect\course::get($id);
if (!$connect) {
    print_error('Invalid Connect ID');
}

$course = $DB->get_record('course', array(
    'id' => $connect->mid
), '*', MUST_EXIST);
$ctx = context_course::instance($course->id);

require_login($course->id);
require_capability('moodle/course:update', $ctx);
require_sesskey();

$PAGE->set_context($ctx);
$PAGE->set_title('Unlinking');
$PAGE->set_url(new \moodle_url('/local/connect/manage/unlink.php', array(
    'id' => $id
)));
$PAGE->set_pagelayout('admin');

$connect->unlink();

redirect(new \moodle_url('/local/connect/manage/course.php', array(
    'mid' => $course->id
)));