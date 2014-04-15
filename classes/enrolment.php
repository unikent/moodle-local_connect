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
        return array("id", "userid", "courseid", "roleid", "deleted");
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
        $this->reset_object_cache();

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
        $this->reset_object_cache();

        $enrol = enrol_get_plugin('manual');
        $instances = $DB->get_records('enrol', array(
            'enrol' => 'manual',
            'courseid' => $this->course->mid,
            'status' => ENROL_INSTANCE_ENABLED
        ), 'sortorder, id ASC');
        $instance = reset($instances);
        $enrol->unenrol_user($instance, $this->user->mid);
    }

    /**
     * Returns true if this is a valid enrolment (i.e. we can create it in Moodle)
     */
    public function is_valid() {
        $this->reset_object_cache();

        if (!$this->course || !$this->user || !$this->role) {
            return false;
        }

        return $this->course->is_in_moodle() && $this->user->is_in_moodle() && $this->role->is_in_moodle();
    }

    /**
     * Check to see if a user is enrolled on this module in Moodle
     */
    public function is_in_moodle() {
        global $CFG;
        require_once($CFG->libdir . "/accesslib.php");

        $this->reset_object_cache();

        if (!$this->course->is_in_moodle()) {
            return false;
        }

        // Get course context.
        $context = \context_course::instance($this->course->mid, IGNORE_MISSING);
        if ($context === false) {
            // The course doesnt exist... it should!..
            // TODO - fire event on the course to reset mid.
            return false;
        }

        // Check enrolment status.
        return is_enrolled($context, $this->user->mid);
    }

    /**
     * Create this enrolment in Moodle
     */
    public function create_in_moodle() {
        $this->reset_object_cache();

        // Create the role.
        if ($this->role && !$this->role->is_in_moodle()) {
            $this->role->create_in_moodle();
        }

        if (!$this->is_valid()) {
            return false;
        }

        if (!enrol_try_internal_enrol($this->course->mid, $this->user->mid, $this->role->mid)) {
            return false;
        }

        // Fire the event.
        $params = array(
            'objectid' => $this->id,
            'relateduserid' => $this->user->mid,
            'courseid' => $this->course->mid,
            'context' => \context_course::instance($this->course->mid)
        );
        $event = \local_connect\event\enrolment_created::create($params);
        $event->add_record_snapshot('connect_enrolments', $this);
        $event->trigger();

        return true;
    }

    /**
     * Returns all enrolments for a given user
     * 
     * @param  string $user A user
     * @return array(local_connect_enrolment) Enrolment objects
     */
    public static function get_for_user($user) {
        global $DB;

        if (!isset($user->id)) {
            return array();
        }

        $objs = $DB->get_records('connect_enrolments', array(
            'userid' => $user->id
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
            'courseid' => $course->id
        ));

        foreach ($objs as &$obj) {
            $enrolment = new enrolment();
            $enrolment->set_class_data($obj);
            $obj = $enrolment;
        }

        return $objs;
    }

    /**
     * Returns all enrolments
     */
    public static function get_all() {
        global $DB;

        $objs = $DB->get_records('connect_enrolments');

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

        $enrolment = new enrolment();
        $enrolment->set_class_data($obj);

        return $enrolment;
    }
}
