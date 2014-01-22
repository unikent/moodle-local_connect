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

namespace local_connect;

defined('MOODLE_INTERNAL') || die();

/**
 * Connect group container
 */
class group {

    /** Our id */
    public $id;

    /** Our Moodle id */
    public $moodle_id;

    /** Our description */
    public $description;

    /** Our module delivery key */
    public $module_delivery_key;

    /** Our session code */
    public $session_code;

    /**
     * Returns all known groups for a given course.
     */
    public static function get_for_course($course) {
        global $CONNECTDB;

        // Select all our groups.
        $sql = "SELECT g.group_id id, g.group_desc description, g.moodle_id
        			FROM `groups` g
                WHERE g.module_delivery_key=:deliverykey AND g.session_code = :sessioncode";

        $data = $CONNECTDB->get_records_sql($sql, array(
            "deliverykey" => $course->module_delivery_key,
            "sessioncode" => $course->session_code
        ));

        // Map to objects.
        foreach ($data as &$group) {
        	$obj = new group();
        	$obj->id = $group->id;
        	$obj->moodle_id = $group->moodle_id;
        	$obj->description = $group->description;
        	$obj->module_delivery_key = $course->module_delivery_key;
        	$obj->session_code = $course->session_code;
        	
        	$group = $obj;
        }

        return $data;
    }
}