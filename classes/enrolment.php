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
     * Returns the ID of the Moodle user.
     */
    public function get_user_id() {
        return $this->userid;
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
        $context = \context_course::instance($this->courseid, MUST_EXIST);

        $sql = "SELECT COUNT(ue.id)
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id=ue.enrolid AND e.courseid=:courseid)
                  JOIN {user} u ON u.id=ue.userid
                  JOIN {role_assignments} ra ON ra.userid=u.id AND contextid=:contextid
                WHERE ue.userid=:userid AND ue.status=:active AND e.status=:enabled AND u.deleted=0 AND ra.roleid=:roleid";
        $params = array(
            'enabled' => ENROL_INSTANCE_ENABLED,
            'active' => ENROL_USER_ACTIVE,
            'userid' => $this->userid,
            'courseid' => $this->courseid,
            'roleid' => $this->roleid,
            'contextid' => $context->id
        );

        return $DB->count_records_sql($sql, $params) > 0;
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
     * Filters a raw SQL set of enrolments
     * 
     * @return array(local_connect_enrolment) Enrolment object
     */
    private static function filter_sql_query_set($data) {
        global $DB;

        // Store of UIDs
        $uid_store = array();

        // Translate each enrolment datum.
        foreach ($data as &$enrolment) {
            // Update the role.
            $shortname = self::translate_role($enrolment->role);
            $role = self::get_role($shortname);
            $enrolment->roleid = $role->id;

            // Map the username if needs be.
            if (!isset($enrolment->userid)) {
                if (!isset($uid_store[$enrolment->username])) {
                    $user = new user($enrolment->username);
                    if (!$user->is_in_moodle()) {
                      $user->create_in_moodle();
                    }

                    $uid_store[$enrolment->username] = $user->get_moodle_id();
                }

                $enrolment->userid = $uid_store[$enrolment->username];
            }

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
     * Returns all enrolments a given user should have
     * 
     * @param  string $username A username
     * @return array(local_connect_enrolment) Enrolment objects
     */
    public static function get_enrolments_for_user($username) {
        global $DB, $CONNECTDB;

        // Grab our user object early on.
        $user = $DB->get_record('user', array('username' => $username));

        // Select all our enrolments.
        $sql = "SELECT e.chksum, e.login username, e.moodle_id enrolmentid, c.moodle_id courseid, e.role, c.module_title FROM `enrollments` e
                    LEFT JOIN `courses` c
                        ON c.module_delivery_key = e.module_delivery_key
                WHERE e.login=:username";
        $data = $CONNECTDB->get_records_sql($sql, array(
            "username" => $username
        ));

        foreach ($data as &$enrolment) {
            // Update the user.
            $enrolment->userid = $user->id;
        }

        return self::filter_sql_query_set($data);
    }

    /**
     * Returns all enrolments the current user should have
     * 
     * @see get_courses
     * @return array(local_connect_enrolment) Enrolment object
     */
    public static function get_my_enrolments() {
        global $USER;
        return self::get_enrolments_for_user($USER->username);
    }

    /**
     * Returns all enrolments for a given course
     * 
     * @param  local_connect_course $course A course
     * @return local_connect_enrolment Enrolment object
     */
    public static function get_enrolments_for_course($course) {
        global $CONNECTDB;

        // Select all our enrolments.
        $sql = "SELECT e.chksum, e.login username, e.moodle_id enrolmentid, c.moodle_id courseid, e.role, c.module_title FROM `enrollments` e
                    LEFT JOIN `courses` c
                        ON c.module_delivery_key = e.module_delivery_key
                WHERE c.module_delivery_key=:deliverykey AND c.session_code = :sessioncode";
        $data = $CONNECTDB->get_records_sql($sql, array(
            "deliverykey" => $course->module_delivery_key,
            "sessioncode" => $course->session_code
        ));

        return self::filter_sql_query_set($data);
    }

    /**
     * Returns all enrolments for a given session code
     */
    public static function get_all($session_code) {
        global $CONNECTDB;

        // Select all our enrolments.
        $sql = "SELECT e.chksum, e.login username, e.moodle_id enrolmentid, c.moodle_id courseid, e.role, c.module_title FROM `enrollments` e
                    LEFT JOIN `courses` c
                        ON c.module_delivery_key = e.module_delivery_key
                WHERE c.session_code = :sessioncode";
        $data = $CONNECTDB->get_records_sql($sql, array(
            "sessioncode" => $session_code
        ));

        return self::filter_sql_query_set($data);
    }

    /**
     * Returns an enrolment, given a session code, module delivery key and login
     */
    public static function get($module_delivery_key, $session_code, $login) {
        global $CONNECTDB;

        // Select all our enrolments.
        $sql = "SELECT e.chksum, e.login username, e.moodle_id enrolmentid, c.moodle_id courseid, e.role, c.module_title FROM `enrollments` e
                    LEFT JOIN `courses` c
                        ON c.module_delivery_key = e.module_delivery_key
                WHERE e.session_code = :sessioncode AND e.module_delivery_key = :module_delivery_key AND e.login = :login";
        $data = $CONNECTDB->get_records_sql($sql, array(
            "module_delivery_key" => $module_delivery_key,
            "sessioncode" => $session_code,
            "login" => $login
        ));

        $array = self::filter_sql_query_set($data);
        return array_pop($array);
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