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
    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return 'connect_enrolments';
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array("id", "mid", "user", "course", "role", "deleted");
    }

    /**
     * A list of immutable fields for this data object.
     */
    protected static function immutable_fields() {
        return array("id");
    }

    /**
     * A list of key fields for this data object.
     */
    protected static function key_fields() {
        return array("id");
    }

    /**
     * Grab the connect user object
     */
    public function _get_user_obj() {
        return user::get($this->user);
    }

    /**
     * Grab the connect user object
     */
    public function _get_course_obj() {
        return course::get($this->course);
    }

    /**
     * Grab the connect role object
     */
    public function _get_role_obj() {
        return role::get($this->role);
    }

    /**
     * Here is the big sync method.
     */
    public function sync($dry = false) {
        // Should we be deleting this?
        if ($this->deleted) {
            if ($this->is_in_moodle()) {
                if (!$dry) {
                    $this->delete();
                }

                return "Deleting Enrolment: $this->id";
            }

            return null;
        }

        // Or creating it?
        if (!$this->is_in_moodle()) {
            if (!$dry) {
                $this->create_in_moodle();
            }

            return "Creating Enrolment: $this->id";
        }
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
            'courseid' => $this->course_obj->mid,
            'status' => ENROL_INSTANCE_ENABLED
        ), 'sortorder, id ASC');
        $instance = reset($instances);
        $enrol->unenrol_user($instance, $this->user_obj->mid);
    }

    /**
     * Returns true if this is a valid enrolment (i.e. we can create it in Moodle)
     */
    public function is_valid() {
        return $this->course_obj->is_in_moodle() && $this->user_obj->is_in_moodle() && $this->role_obj->is_in_moodle();
    }

    /**
     * Check to see if a user is enrolled on this module in Moodle
     */
    public function is_in_moodle() {
        global $CFG;
        require_once($CFG->libdir . "/accesslib.php");

        // Get course context.
        $context = \context_course::instance($this->course_obj->mid, MUST_EXIST);

        // Check enrolment status.
        return is_enrolled($context, $this->user_obj->mid);
    }

    /**
     * Create this enrolment in Moodle
     */
    public function create_in_moodle() {
        return enrol_try_internal_enrol($this->course_obj->mid, $this->user_obj->mid, $this->role_obj->mid);
    }

    /**
     * Returns all enrolments for a given user
     * 
     * @param  string $user A user
     * @return array(local_connect_enrolment) Enrolment objects
     */
    public static function get_for_user($user) {
        global $DB;

        $objs = $DB->get_records('connect_enrolments', array(
            'user' => $user->id
        ));

        foreach ($objs as &$obj) {
            $enrolment = new enrolment();
            $enrolment->set_class_data($obj);
            $obj = $enrolment;
        }

        return $objs;
    }

    /**
     * Returns all enrolments the current user should have
     * 
     * @see get_all
     * @return array(local_connect_enrolment) Enrolment object
     */
    public static function get_my_enrolments() {
        global $USER;
        $user = user::get_by_username($USER->username);
        return self::get_for_user($user);
    }

    /**
     * Returns all enrolments for a given course
     * 
     * @param  local_connect_course $course A course
     * @return local_connect_enrolment Enrolment object
     */
    public static function get_for_course($course) {
        global $DB;

        $objs = $DB->get_records('connect_enrolments', array(
            'course' => $course->id
        ));

        foreach ($objs as &$obj) {
            $enrolment = new enrolment();
            $enrolment->set_class_data($obj);
            $obj = $enrolment;
        }

        return $objs;
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
    public static function get_by_uid($module_delivery_key, $session_code, $login) {
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
}
