<?php
/**
 * /tmp/phptidy-sublime-buffer.php
 *
 * @package default
 */


define('AJAX_SCRIPT', true);

require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(__FILE__) . '/locallib.php';

if (!\local_connect\utils::is_enabled()) {
    die(json_encode(array("error" => "Connect has been disabled")));
}

if (!\local_connect\utils::can_course_manage()) {
    die(json_encode(array("error" => "You do not have access to view this")));
}

/**
 * We now have two choices:
 *   1) We can use the fancy new stuff
 *   2) The fancy new stuff does not do what we want yet, so we use the old stuff.
 */

//
// New stuff
//
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
        $category_restrictions = isset($_GET['category_restrictions']) ? json_decode(urldecode($_GET['category_restrictions'])) : array();
        $courses = \local_connect\course::get_by_category($category_restrictions, false);

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