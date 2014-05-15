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

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');

if (!\local_connect\util\helpers::is_enabled()) {
    die(json_encode(array("error" => "Connect has been disabled")));
}

if (!\local_connect\util\helpers::can_course_manage()) {
    die(json_encode(array("error" => "You do not have access to view this")));
}

switch ($_SERVER['PATH_INFO']) {
    case '/courses/schedule':
    case '/courses/schedule/':
        header('Content-type: application/json');
        $input = json_decode(file_get_contents('php://input'));
        if ($input === null) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity');
        } else {
            $result = \local_connect\course::schedule_all($input);
            echo json_encode($result);
        }
        die;
    case '/courses/disengage/':
        header('Content-type: application/json');
        $input = json_decode(file_get_contents('php://input'));
        if ($input === null) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity');
        } else {
            $result = \local_connect\course::disengage_all($input);
            echo json_encode($result);
        }
        die;
    case '/courses/merge':
    case '/courses/merge/':
        header('Content-type: application/json');
        $input = json_decode(file_get_contents('php://input'));
        if (null == $input) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity');
        } else {
            $result = \local_connect\course::process_merge($input);
            if (is_array($result) && isset($result['error_code'])) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 422');
            } else {
                header($_SERVER['SERVER_PROTOCOL'] . ' 204 Created');
            }

            echo json_encode($result);
        }
        exit(0);
    case '/courses/unlink':
    case '/courses/unlink/':
        header('Content-type: application/json');
        $input = json_decode(file_get_contents('php://input'));
        if (null == $input) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity');
        } else {
            $result = \local_connect\course::process_unlink($input->courses);
            echo json_encode($result);
        }
        exit(0);
    case '/courses':
    case '/courses/':
        header('Content-type: application/json');
        $restrictions = isset($_GET['category_restrictions']) ? json_decode(urldecode($_GET['category_restrictions'])) : array();
        $courses = array();
        if (!empty($restrictions)) {
            $courses = \local_connect\course::get_by_category($restrictions, true);
        } else {
            $courses = \local_connect\course::get_all(true);
        }

        // Map campus IDs.
        $campusids = array();
        $campuses = $DB->get_records('connect_campus');
        foreach ($campuses as $campus) {
            $campusids[$campus->id] = $campus->name;
        }

        foreach ($courses as &$course) {
            if (isset($campusids[$course->campusid])) {
                $course->campus = $campusids[$course->campusid];
            }
        }

        echo json_encode($courses);
        die;
    default:
        // Do nothing.
    break;
}