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

require_once($CFG->libdir . "/enrollib.php");
require_once($CFG->libdir . "/accesslib.php");

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
        return array("id", "userid", "courseid", "roleid");
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
     * Here is the big sync method.
     */
    public function sync($dry = false) {
        debugging("You should not be using this! The enrol sync does this now.");
    }

    /**
     * Delete from Moodle
     * 
     * @return boolean
     */
    public function delete() {
        debugging("You should not be using this! The enrol sync does this now.");
    }

    /**
     * Returns true if this is a valid enrolment (i.e. we can create it in Moodle)
     */
    public function is_valid() {
        if (!$this->course) {
            return false;
        }

        return $this->course->is_in_moodle();
    }

    /**
     * Check to see if a user is enrolled on this module in Moodle
     */
    public function is_in_moodle() {
        if (!$this->course->is_in_moodle()) {
            return false;
        }

        // Get course context.
        $context = \context_course::instance($this->course->mid, IGNORE_MISSING);
        if ($context === false) {
            return false;
        }

        // Check enrolment status.
        return is_enrolled($context, $this->user->mid);
    }

    /**
     * Check to see if a user is enrolled on this module in Moodle
     * also checks they have the correct role.
     */
    public function is_in_moodle_precise() {
        if (!$this->is_in_moodle()) {
            return false;
        }

        // Get course context.
        $context = \context_course::instance($this->course->mid, IGNORE_MISSING);
        if ($context === false) {
            return false;
        }

        // Check enrolment status.
        return user_has_role_assignment($this->user->mid, $this->role->mid, $context->id);
    }

    /**
     * Create this enrolment in Moodle
     */
    public function create_in_moodle() {
        // Create the user.
        if ($this->user && !$this->user->is_in_moodle()) {
            if (!$this->user->create_in_moodle()) {
                return false;
            }
        }

        // Create the role.
        if ($this->role && !$this->role->is_in_moodle()) {
            $this->role->create_in_moodle();
        }

        if (!$this->is_valid()) {
            return false;
        }

        // Do the enrolment.
        $instance = $this->course->get_enrol_instance();
        if ($instance && $instance->status == ENROL_INSTANCE_ENABLED) {
            $enrol = enrol_get_plugin('connect');
            $enrol->enrol_user($instance, $this->user->mid, $this->role->mid);
        }

        if (!$this->is_in_moodle()) {
            $msg = "Enrol '{$this->user->mid}' on '{$this->course->mid}' as a '{$this->role->name}' failed.";
            \local_connect\util\helpers::error($msg);
            return false;
        }

        // Fire the event.
        $params = array(
            'objectid' => $this->id,
            'relateduserid' => $this->user->mid,
            'courseid' => $this->course->mid,
            'context' => \context_course::instance($this->course->mid),
            'other' => array(
                'userid' => $this->userid
            )
        );
        $event = \local_connect\event\enrolment_created::create($params);
        $event->trigger();

        return true;
    }

    /**
     * Returns all enrolments for a given course MID.
     * 
     * @return array(local_connect_enrolment) Enrolment objects
     */
    public static function get_by_course_mid($mid, $userid = null) {
        global $DB;

        $sql = <<<SQL
            SELECT ce.*
            FROM {connect_enrolments} ce
            INNER JOIN {connect_course} c ON c.id=ce.courseid
            INNER JOIN {connect_user} cu ON cu.id=ce.userid
            WHERE c.mid = :mid
SQL;

        $params = array(
            "mid" => $mid
        );

        if ($userid !== null) {
            $sql .= " AND cu.id=:userid";
            $params["userid"] = $userid;
        }

        $objs = $DB->get_records_sql($sql, $params);

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

        $obj = user::get_by('login', $USER->username);
        return self::get_by("userid", $obj->id, true);
    }

    /**
     * Returns all enrolments for a given category
     * 
     * @param  local_connect_category $category A category
     * @return local_connect_enrolment Enrolment object
     */
    public static function get_for_category($category) {
        global $DB;

        $sql = <<<SQL
        SELECT ce.* FROM {connect_enrolments} ce
        INNER JOIN {connect_course} c
        WHERE c.category = :category
SQL;

        $objs = $DB->get_records_sql($sql, array(
            'category' => $category->id
        ));

        foreach ($objs as &$obj) {
            $enrolment = new enrolment();
            $enrolment->set_class_data($obj);
            $obj = $enrolment;
        }

        return $objs;
    }

    /**
     * Returns an enrolment, given a user and a course
     */
    public static function get_for_user_and_course($user, $course) {
        global $DB;

        $obj = $DB->get_record('connect_enrolments', array(
            "userid" => $user->id,
            "courseid" => $course->id
        ));

        if (!$obj) {
            return null;
        }

        $enrolment = new enrolment();
        $enrolment->set_class_data($obj);

        return $enrolment;
    }

    /**
     * Returns an enrolment, given a course and a role
     */
    public static function get_for_course_and_role($course, $role) {
        global $DB;

        $records = $DB->get_records('connect_enrolments', array(
            "courseid" => $course->id,
            "roleid" => $role->id
        ));

        $enrolments = array();

        foreach ($records as $record) {
            $enrolment = new enrolment();
            $enrolment->set_class_data($record);

            $enrolments[] = $enrolment;
        }

        return $enrolments;
    }
}
