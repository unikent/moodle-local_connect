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
 * @deprecated See web service methods: local_connect_push_module, local_connect_merge_module, local_connect_unlink_module
 * @package    local_connect
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

if (!\local_connect\util\helpers::is_enabled()) {
    die(json_encode(array("error" => "Connect has been disabled")));
}

if (!\local_connect\util\helpers::can_category_manage()) {
    die(json_encode(array("error" => "You do not have access to view this")));
}

$action = required_param('action', PARAM_ALPHA);
switch ($action) {
    case 'schedule':
        $courses = required_param('courses', PARAM_RAW);
        $courses = json_decode($courses);

        $result = \local_connect\course::schedule_all($courses);

        echo $OUTPUT->header();
        echo json_encode($result);
    break;

    case 'disengage':
        $course = required_param('course', PARAM_INT);
        $obj = \local_connect\course::get($course);
        if ($obj) {
            $obj->delete();
        }

        echo $OUTPUT->header();
        echo json_encode(array('result' => 'success'));
    break;

    case 'merge':
        $courses = required_param('courses', PARAM_RAW);
        $courses = json_decode($courses);

        $result = \local_connect\course::process_merge($courses);
        if (is_array($result) && isset($result['error_code'])) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422');
        } else {
            header($_SERVER['SERVER_PROTOCOL'] . ' 204 Created');
        }

        echo $OUTPUT->header();
        echo json_encode($result);
    break;

    case 'unlink':
        $course = required_param('course', PARAM_INT);
        $obj = \local_connect\course::get($course);
        if ($obj) {
            $obj->unlink();
        }

        echo $OUTPUT->header();
        echo json_encode(array('result' => 'success'));
    break;
}