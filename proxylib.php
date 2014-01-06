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
 * A library for new-style proxy functions
 *
 * @package    local
 * @subpackage connect
 * @copyright  2013 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/stomp/Stomp.php');

/**
 * Proxy functions
 */
function lcproxy_publish($queue, $message) {
    global $CFG;
    $stomp = new \FuseSource\Stomp\Stomp($CFG->connect->stomp);
    try {
        $stomp->connect();
    } catch (\FuseSource\Stomp\Exception\StompException $e) {
        die("Couldnt connect to STOMP! " . $e);
    }
    $stomp->send($queue, $message);
    $stomp->disconnect();
}

/**
 * Schedule courses
 */
function lcproxy_scheduleCourses() {
    $json = json_decode(file_get_contents("php://input"));
    $courses = $json->courses;

    if (empty($courses) || count($courses) > 200) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 422 Invalid number of courses' . $contents, true, 422);
        die("Invalid number of courses");
    }

    $pdo = connect_db();

    $result = array();
    foreach ($courses as $in_course) {
        $course = lcproxy_getCourse($pdo, $in_course->id);

        // Are we scheduled?
        if (in_array($course->state, array(2, 4, 6, 8, 10, 12))) {
            // Cannot continue with this one
            continue;
        }

        $course->module_code = $in_course->code;
        $course->module_title = $in_course->title;
        $course->synopsis = $in_course->synopsis;
        $course->category_id = $in_course->category;

        if ($course->category_id == 0) {
            // Cannot continue with this one
            $result[] = array("error_code" => "category_is_zero", "id" => $course->chksum);
            continue;
        }

        if (!lcproxy_isCourseUnique($pdo, $course->session_code, $course->module_code, $course->chksum)) {
            // Cannot continue with this one
            $result[] = array("error_code" => "duplicate", "id" => $course->chksum);
            continue;
        }

        lcproxy_updateForSchedule($pdo, $course->chksum, $course->module_code, $course->module_title, $course->synopsis, $course->category_id);

        lcproxy_publish('/queue/connect.job.create_course', $course->chksum);
    }

    if (count($result) > 0) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 422 error', true, 422);
    }

    echo json_encode($result);
}