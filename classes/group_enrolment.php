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
 * Connect group enrolment container
 */
class group_enrolment extends data
{
    /** Our Connect group - Dont rely on this being set! Use ->group */
    private $_group;

    /** Our Moodle user id - Dont rely on this being set! Use get_moodle_user_id() */
    private $_moodle_user_id;

    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return 'group_enrollments';
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array("group_id", "group_desc", "module_delivery_key", "ukc", "login", "session_code", "chksum", "sink_deleted", "moodle_id", "state", "created_at", "updated_at", "id_chksum", "last_checked");
    }

    /**
     * A list of immutable fields for this data object.
     */
    protected static function immutable_fields() {
        return array("group_id", "module_delivery_key", "session_code", "login");
    }

    /**
     * A list of key fields for this data object.
     */
    protected static function key_fields() {
        return array("group_id", "login");
    }

    /**
     * Sync method
     */
    public function sync($dry = false) {
        // Should we be deleting this?
        if ($this->sink_deleted) {
            if ($this->is_in_moodle()) {
                if (!$dry) {
                    $this->delete();
                }

                return "Deleting Group Enrolment: $this->chksum";
            }

            return null;
        }

        // Easy option.
        if (!$this->is_in_moodle()) {
            if (!$dry) {
                $this->create_in_moodle();
            }

            return "Creating Group Enrolment: " . $this->chksum;
        }
    }

    /**
     * Grab our Connect Group
     * @return unknown
     */
    protected function _get_group() {
        if (!isset($this->_group)) {
            $this->_group = group::get($this->group_id);
        }

        return $this->_group;
    }


    /**
     * Grab our Moodle User's ID
     * @return unknown
     */
    private function get_moodle_user_id() {
        $user = user::get($this->login);
        $this->_moodle_user_id = $user->moodle_id;
        return $this->_moodle_user_id;
    }


    /**
     * Can this be added to Moodle yet?
     * @return unknown
     */
    public function is_valid() {
        $group = $this->group;
        $groupid = $group->moodle_id;
        if (empty($groupid)) {
            return false;
        }

        $userid = $this->get_moodle_user_id();
        if (empty($userid)) {
            return false;
        }

        return true;
    }


    /**
     * Check to see if this exists in Moodle
     * @return unknown
     */
    public function is_in_moodle() {
        global $DB;

        if (!$this->is_valid()) {
            return false;
        }

        $group = $this->group;
        $userid = $this->get_moodle_user_id();

        return groups_is_member($group->moodle_id, $userid);
    }


    /**
     * Create this group enrolment in Moodle
     * @return unknown
     */
    public function create_in_moodle() {
        global $CFG;
        require_once $CFG->dirroot.'/group/lib.php';

        if (!$this->is_valid() || $this->sink_deleted) {
            return false;
        }

        $group = $this->group;
        $userid = $this->get_moodle_user_id();

        return groups_add_member($group->moodle_id, $userid);
    }

    /**
     * Delete from Moodle
     * 
     * @return boolean
     */
    public function delete() {
        global $CFG;
        require_once $CFG->dirroot.'/group/lib.php';

        if (!$this->is_valid()) {
            return false;
        }

        $group = $this->group;
        $userid = $this->get_moodle_user_id();

        groups_remove_member($group->moodle_id, $userid);
    }

    /**
     * Filter an SQL Query Set into objects (to keep it DRY)
     */
    private static function filter_sql_query_set($data) {
        foreach ($data as &$group_enrolment) {
            $obj = new group_enrolment();
            $obj->set_class_data($group_enrolment);

            $group_enrolment = $obj;
        }

        return $data;
    }


    /**
     * Returns a group enrolment, given a group ID and a username.
     * @param unknown $group
     * @return unknown
     */
    public static function get($group_id, $username) {
        global $CONNECTDB;

        $data = $CONNECTDB->get_record("group_enrollments", array(
            "group_id" => $group_id,
            "login" => $username
        ));

        $obj = new group_enrolment();
        $obj->set_class_data($data);

        return $obj;
    }

    /**
     * Returns all known group enrollments for a given group.
     * @param unknown $group
     * @return unknown
     */
    public static function get_for_group($group) {
        global $CONNECTDB;

        // Select all our groups.
        $data = $CONNECTDB->get_records("group_enrollments", array(
            "group_id" => $group->id
        ), '', 'chksum, login, group_id, sink_deleted');

        return self::filter_sql_query_set($data);
    }

    /**
     * Returns all group enrolments for a given course
     * 
     * @param  local_connect_course $course A course
     * @return local_connect_enrolment Enrolment object
     */
    public static function get_for_course($course) {
        global $CONNECTDB;

        // Select all our group enrolments.
        $data = $CONNECTDB->get_records("group_enrollments", array(
            "module_delivery_key" => $course->module_delivery_key,
            "session_code" => $course->session_code
        ), '', 'chksum, login, group_id, sink_deleted');

        return self::filter_sql_query_set($data);
    }

    /**
     * Returns all known group enrolments for a given session code.
     * @param unknown $session_code
     * @return unknown
     */
    public static function get_all($session_code) {
        global $CONNECTDB;

        // Select all our groups.
        $data = $CONNECTDB->get_records("group_enrollments", array(
            "session_code" => $session_code
        ), '', 'chksum, login, group_id, sink_deleted');

        return self::filter_sql_query_set($data);
    }


}
