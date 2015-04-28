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

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');

if (!\local_connect\util\helpers::is_enabled()) {
    die(json_encode(array("error" => "Connect has been disabled")));
}

if (!\local_connect\util\helpers::can_course_manage()) {
    die(json_encode(array("error" => "You do not have access to view this")));
}

$action = required_param('action', PARAM_ALPHA);

$input = json_decode(file_get_contents('php://input'));
if ($input == null) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity');
    die;
}

switch ($action) {
    case 'schedule':
        $result = \local_connect\course::schedule_all($input);
        if (is_array($result) && isset($result['error_code'])) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422');
        } else {
            header($_SERVER['SERVER_PROTOCOL'] . ' 204 Created');
        }
        echo $OUTPUT->header();
        echo json_encode($result);
    break;

    case 'disengage':
        $result = \local_connect\course::disengage_all($input);
        echo $OUTPUT->header();
        echo json_encode($result);
    break;

    case 'merge':
        $result = \local_connect\course::process_merge($input);
        if (is_array($result) && isset($result['error_code'])) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422');
        } else {
            header($_SERVER['SERVER_PROTOCOL'] . ' 204 Created');
        }

        echo $OUTPUT->header();
        echo json_encode($result);
    break;

    case 'unlink':
        $result = \local_connect\course::process_unlink($input->courses);
        echo $OUTPUT->header();
        echo json_encode($result);
    break;
}