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
 * @package    core_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect;

defined('MOODLE_INTERNAL') || die();

/**
 * Connect enrolment container
 */
class enrolment {

    /** The Moodle ID of the user this relates to */
    private $userid;

    /** The Moodle ID of the course this relates to */
    private $courseid;

    /** The Moodle ID of the role this relates to */
    private $roleid;

    /** The module title of this enrolment */
    private $moduletitle;

    /**
     * Our constructor
     */
    public function __construct($userid, $courseid, $roleid, $moduletitle) {
        $this->userid = $userid;
        $this->courseid = $courseid;
        $this->roleid = $roleid;
        $this->moduletitle = $moduletitle;
    }

    /**
     * Returns true if this is a valid enrolment
     */
    public function is_valid() {
        return !empty($this->courseid) && !empty($this->roleid) && !empty($this->userid);
    }

    /**
     * Check to see if a user is enrolled on this module in Moodle
     */
    public function is_in_moodle() {
        global $DB;

        // Get course context.
        $context = context_course::instance($this->courseid, MUST_EXIST);

        $sql = "SELECT ue.*
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id=ue.enrolid AND e.courseid=:courseid)
                  JOIN {user} u ON u.id=ue.userid
                  JOIN {role_assignments} ra ON ra.userid=u.id AND contextid=:contextid
                WHERE ue.userid=:userid AND ue.status=:active AND e.status=:enabled AND u.deleted=0 AND ra.roleid=:roleid";

        return (!$enrolments = $DB->get_records_sql($sql, array(
            'enabled' => ENROL_INSTANCE_ENABLED,
            'active' => ENROL_USER_ACTIVE,
            'userid' => $this->userid,
            'courseid' => $this->courseid,
            'roleid' => $this->roleid,
            'contextid' => $context->id
        )));
    }

    /**
     * Create this enrolment in Moodle
     */
    public function create_in_moodle() {
        return enrol_try_internal_enrol($this->courseid, $this->userid, $this->roleid);
    }

    /**
     * Returns the module name
     */
    public function __toString() {
        return $this->moduletitle;
    }

    /**
     * Returns all courses a given user should be enrolled on
     * 
     * @param  string $username A username
     * @return core_connect_enrolment Enrolment object
     */
    public static function get_courses($username) {
        global $DB, $CONNECTDB;

        // Grab our user object early on.
        $user = $DB->get_record('user', array('username' => $username));

        // Select all our courses.
        $sql = "SELECT e.login username, e.moodle_id enrolmentid, c.moodle_id courseid, e.role, c.module_title FROM `enrollments` e
                    LEFT JOIN `courses` c
                        ON c.module_delivery_key = e.module_delivery_key
                WHERE e.login=:username";
        $data = $CONNECTDB->get_records_sql($sql, array(
            "username" => $username
        ));

        // Translate each enrolment datum.
        foreach ($data as &$enrolment) {
            // Update the role.
            $shortname = self::translate_role($enrolment->role);
            $role = self::get_role($shortname);
            $enrolment->roleid = $role->id;

            // Update the user.
            $enrolment->userid = $user->id;

            // Create an object for this enrolment.
            $enrolment = new static(
                $enrolment->userid,
                $enrolment->courseid,
                $enrolment->roleid,
                $enrolment->module_title
            );
        }

        // Filter out invalid courses.
        $data = array_filter($data, function($enrolment) {
            return $enrolment->is_valid();
        });

        return $data;
    }

    /**
     * Returns all courses the current user should be enrolled on.
     * 
     * @see get_courses
     * @return core_connect_enrolment Enrolment object
     */
    public static function get_my_courses() {
        global $USER;
        return self::get_courses($USER->username);
    }


    /**
     * Translates a Connect role into Moodle role
     * 
     * @param  string $role A role grabbed out the connect database
     * @return string The Moodle version of this role
     */
    private static function translate_role($role) {
        switch ($role) {
            case "student":
            case "teacher":
                return "sds_$role";
            default:
                return $role;
        }
    }

    /**
     * Returns the database object for a given role
     * 
     * @param string $shortname A shortname for a role
     */
    private static function get_role($shortname) {
        global $DB;
        static $cache;

        // Initialize cache.
        if (!is_array($cache)) {
            $cache = array();
        }

        // Check cache.
        if (isset($cache[$shortname])) {
            return $cache[$shortname];
        }

        // Grab new data.
        $role = $DB->get_record('role', array('shortname' => $shortname));

        // Set cache.
        $cache[$shortname] = $role;

        return $role;
    }
}