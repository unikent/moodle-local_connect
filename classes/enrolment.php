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
class enrolment extends data
{
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
        parent::__construct();

        $this->userid = $userid;
        $this->courseid = $courseid;
        $this->roleid = $roleid;
        $this->moduletitle = $moduletitle;
    }

    /**
     * The name of our connect table.
     */
    protected function get_table() {
        return 'enrollments';
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final function valid_fields() {
        return array("ukc", "login", "title", "initials", "family_name", "session_code", "module_delivery_key", "role", "chksum", "moodle_id", "sink_deleted", "state", "created_at", "updated_at", "id_chksum", "last_checked");
    }

    /**
     * A list of immutable fields for this data object.
     */
    protected function immutable_fields() {
        return array("ukc", "login", "module_delivery_key", "session_code", "role");
    }

    /**
     * A list of key fields for this data object.
     */
    protected function key_fields() {
        return array("login", "module_delivery_key", "session_code");
    }

    /**
     * Delete from Moodle
     * 
     * @return boolean
     */
    public function delete() {
        global $DB;

        $enrol = enrol_get_plugin('manual');
        $instances = $DB->get_records('enrol', array(
            'enrol' => 'manual',
            'courseid' => $this->courseid,
            'status' => ENROL_INSTANCE_ENABLED
        ), 'sortorder, id ASC');
        $instance = reset($instances);
        $enrol->unenrol_user($instance, $this->userid);
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
        global $CFG;
        require_once($CFG->libdir . "/accesslib.php");

        // Get course context.
        $context = \context_course::instance($this->courseid, MUST_EXIST);

        // Check enrolment status.
        return is_enrolled($context, $this->userid);
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
                    $user = user::get($enrolment->username);
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
    public static function get_for_user($username) {
        global $DB, $CONNECTDB;

        // Grab our user object early on.
        $user = $DB->get_record('user', array('username' => $username));
        if (!$user) {
            return array();
        }

        // Select all our enrolments.
        $sql = "SELECT e.chksum, e.login username, e.moodle_id enrolmentid, c.moodle_id courseid, e.role, c.module_title, e.module_delivery_key
                FROM {enrollments} e
                    LEFT JOIN {courses} c
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
        return self::get_for_user($USER->username);
    }

    /**
     * Returns all enrolments for a given course
     * 
     * @param  local_connect_course $course A course
     * @return local_connect_enrolment Enrolment object
     */
    public static function get_for_course($course) {
        global $CONNECTDB;

        // If this course has children, get the enrolments for those instead.
        if ($course->has_children()) {
            $data = array();

            foreach ($course->children as $child) {
                if ($child != $course) {
                    $data = array_merge($data, self::get_for_course($child));
                }
            }

            return $data;
        }

        // Select all our enrolments.
        $sql = "SELECT e.chksum, e.login username, e.moodle_id enrolmentid, c.moodle_id courseid, e.role, c.module_title, e.module_delivery_key
                FROM {enrollments} e
                    LEFT JOIN {courses} c
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
        $sql = "SELECT e.chksum, e.login username, e.moodle_id enrolmentid, c.moodle_id courseid, e.role, c.module_title, e.module_delivery_key
                FROM {enrollments} e
                    LEFT JOIN {courses} c
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
        $sql = "SELECT e.chksum, e.login username, e.moodle_id enrolmentid, c.moodle_id courseid, e.role, c.module_title, e.module_delivery_key
                FROM {enrollments} e
                    LEFT JOIN {courses} c
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

        // Create the role if it doesnt exist.
        self::create_role($shortname);

        // Grab new data.
        $role = $DB->get_record('role', array(
            'shortname' => $shortname
        ));

        return $role;
    }

    /**
     * Create the given role if it doesnt already exist.
     */
    private static function create_role($shortname) {
        global $DB, $CFG;

        static $data_map = array(
            "sds_student" => array(
                "Student (SDS)",
                "sds_student",
                "Students generally have fewer privileges within a course.",
                "student"
            ),
            "sds_teacher" => array(
                "Teacher (SDS)",
                "sds_teacher",
                "Teachers can do anything within a course, including changing the activities and grading students.",
                "editingteacher"
            ),
            "convenor" => array(
                "Convenor (SDS)",
                "convenor",
                "A Convenor has the same permissions as a teacher, but can manually enrol teachers.",
                "editingteacher"
            )
        );

        if (!isset($data_map[$shortname])) {
            throw new \moodle_exception("Invalid Connect Role - $shortname!");
        }

        if (!$DB->record_exists('role', array('shortname' => $shortname))) {
            require_once($CFG->libdir . "/accesslib.php");

            // Create it!
            call_user_func_array("create_role", $data_map[$shortname]);
        }
    }
}
