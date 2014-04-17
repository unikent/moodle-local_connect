<?php

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/user/lib.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/group/lib.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/lib/accesslib.php');
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$res = array();

/*
 * expects input
 * [
 *    { 'group_desc' => 'something', 'moodle_course_id' => 321
 *        'isa' => 'NEW' or 'DELETE', 'moodle_group_id' => 12312 or nil
 *        'group_id' => 354 }
 *    ...
 * ]
 *
 * sends output :
 * [
 *     { 'result' => 'ok' or 'error', 'exception' => if error
 *         'moodle_group_id' => 345, 'in' => input }
 * ]
 */

foreach (json_decode(file_get_contents('php://stdin')) as $c) {
    $tr = array();
    try {
        // Try and find the course.
        $mdl_connect_course = $DB->get_record('connect_course', array(
            "mid" => $c->moodle_course_id
        ));

        if ($mdl_connect_course === false) {
            throw new moodle_exception("Course does not exist!");
        }

        // Try and find the group.
        $mdl_connect_group_id = $DB->get_field('connect_group', 'id', array(
            "name" => $c->group_desc,
            "courseid" => $mdl_connect_course->id
        ));

        // Grab the ID of our new Connect's version of the course.
        if ($mdl_connect_group_id === false) {
            // Create it!
            $data = array(
                "courseid" => $mdl_connect_course->id,
                "name" => $c->group_desc
            );

            if (!empty($c->moodle_group_id)) {
                $data["mid"] = $c->moodle_group_id;
            }

            $mdl_connect_group_id = $DB->insert_record('connect_group', $data);
        }

        $group = \local_connect\group::get($mdl_connect_group_id);

        switch ($c->isa) {
            case "NEW":
                $group->create_in_moodle("Delivery groups");
            break;

            case "DELETE":
                $group->delete();
            break;

            default:
            throw new moodle_exception('Dont understand: ' . $c->isa);
        }

        $tr = array( 'result' => 'ok', 'moodle_group_id' => $group->id, 'in' => $c );
    } catch (Exception $e) {
        $tr = array(
            'result' => 'error',
            'in' => $c,
            'exception' => $e
        );
    }
    $res[] = $tr;
}

echo json_encode($res);
