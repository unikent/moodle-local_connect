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
class group extends data
{
    /** Our chksum */
    public $chksum;

    /** Our id */
    public $id;

    /** Our description */
    public $description;

    /** Our course's module delivery key */
    public $module_delivery_key;

    /** Our course's session code */
    public $session_code;

    /** Our Connect course - Dont rely on this being set! Use get_course() */
    private $course;

    /** Our Moodle id - Dont rely on this being set! Use get_moodle_id() */
    private $moodle_id;

    /**
     * A list of valid fields for this data object.
     */
    protected final function valid_fields() {
        return array("group_id", "group_desc", "module_delivery_key", "session_code", "moodle_id", "state", "created_at", "updated_at", "chksum", "id_chksum", "last_checked");
    }

    /**
     * A list of immutable fields for this data object.
     */
    protected function immutable_fields() {
        return array("group_id", "module_delivery_key", "session_code");
    }

    /**
     * A list of key fields for this data object.
     */
    protected function key_fields() {
        return array("group_id");
    }

    /**
     * The big sync method.
     */
    public function sync($dry = false) {
        global $DB;

        // The easiest path!
        if (!$this->is_in_moodle()) {
            if (!$dry) {
                $this->create_in_moodle();
            }

            return 'Creating group: ' . $this->chksum;
        }

        // We are currently in Moodle!
        $group = $DB->get_record('groups', array(
            'id' => $this->get_moodle_id()
        ), 'id,courseid,name');

        // Does our data match up?
        if ($group->name !== $this->description) {
            if (!$dry) {
                $data = new \stdClass();
                $data->id = $group->id;
                $data->name = $this->description;
                $data->courseid = $group->courseid;
                groups_update_group($data);
            }

            return 'Updating group: ' . $this->chksum;
        }
    }

    /**
     * Grab our Connect Course
     * @return unknown
     */
    public function get_course() {
        if (isset($this->course)) {
            return $this->course;
        }

        $this->course = course::get_course_by_uid($this->module_delivery_key, $this->session_code);
        return $this->course;
    }


    /**
     * Grab our Moodle ID
     * @return unknown
     */
    public function get_moodle_id() {
        global $DB;

        if (empty($this->moodle_id)) {
            $course = $this->get_course();
            if (!$course) {
                $this->moodle_id = null;
                return null;
            }

            $group = $DB->get_record('groups', array(
                "courseid" => $course->moodle_id,
                "name" => $this->description
            ));

            $this->moodle_id = $group ? $group->id : null;
        }

        return $this->moodle_id;
    }


    /**
     * Grab (or create) our grouping ID
     * @return unknown
     */
    private function get_or_create_grouping() {
        global $DB;

        $course = $this->get_course();

        $grouping = $DB->get_record('groupings', array(
            'name' => 'Seminar groups',
            'courseid' => $this->moodle_id
        ));

        // Create?
        if (!$grouping) {
            $data = new \stdClass();
            $data->name = "Seminar groups";
            $data->courseid = $course->moodle_id;
            $data->description = '';
            return groups_create_grouping($data);
        }

        return $grouping->id;
    }


    /**
     * Check to see if this exists in Moodle
     * @return unknown
     */
    public function is_in_moodle() {
        return $this->get_moodle_id() !== null;
    }


    /**
     * Create this group in Moodle
     * @return unknown
     */
    public function create_in_moodle() {
        global $CFG, $CONNECTDB;

        $course = $this->get_course();

        if (empty($course->moodle_id)) {
            return false;
        }

        require_once $CFG->dirroot . '/group/lib.php';

        $data = new \stdClass();
        $data->name = $this->description;
        $data->courseid = $course->moodle_id;
        $data->description = '';

        $this->moodle_id = groups_create_group($data);

        if ($this->moodle_id === false) {
            return false;
        }

        // Set Moodle ID.
        $CONNECTDB->set_field('groups', 'moodle_id', $this->moodle_id, array(
            'chksum' => $this->chksum,
            'group_id' => $this->id
        ));

        // Grab our grouping.
        $grouping_id = $this->get_or_create_grouping();

        // And add this group to the grouping.
        groups_assign_grouping($grouping_id, $this->moodle_id);

        // Sync enrolments.
        $this->sync_group_enrolments();

        return true;
    }


    /**
     * Syncs group enrollments for this Group
     * @todo Updates/Deletions
     */
    public function sync_group_enrolments() {
        $enrolments = group_enrolment::get_for_group($this);
        foreach ($enrolments as $enrolment) {
            if (!$enrolment->is_in_moodle()) {
                $enrolment->create_in_moodle();
            }
        }
    }

    /**
     * Returns the number of students enrolled in this group.
     */
    public function count_students() {
        // First we need a list of all students.
        $students = user::get_students();

        // Now we need all enrolments.
        $enrolments = group_enrolment::get_for_group($this);

        $result = 0;

        // Count the students out.
        foreach ($enrolments as $enrolment) {
            if (isset($students[$enrolment->login])) {
                $result++;
            }
        }

        return $result;
    }

    /**
     * Returns the number of staff enrolled in this group.
     */
    public function count_staff() {
        // First we need a list of all staff.
        $staff = user::get_staff();

        // Now we need all enrolments.
        $enrolments = group_enrolment::get_for_group($this);

        $result = 0;

        // Count the staff out.
        foreach ($enrolments as $enrolment) {
            if (isset($staff[$enrolment->login])) {
                $result++;
            }
        }

        return $result;
    }

    /**
     * Returns a group specified by ID
     * @param unknown $uid
     * @return unknown
     */
    public static function get($uid) {
        global $CONNECTDB;

        $group = $CONNECTDB->get_record('groups', array(
            'group_id' => $uid
        ));

        $obj = new group();
        $obj->id = $uid;
        $obj->chksum = $group->chksum;
        $obj->moodle_id = $group->moodle_id;
        $obj->description = $group->group_desc;
        $obj->module_delivery_key = $group->module_delivery_key;
        $obj->session_code = $group->session_code;

        return $obj;
    }


    /**
     * Returns all known groups for a given course.
     * @param unknown $course
     * @return unknown
     */
    public static function get_for_course($course) {
        global $CONNECTDB;

        // Select all our groups.
        $data = $CONNECTDB->get_records("groups", array(
            "module_delivery_key" => $course->module_delivery_key,
            "session_code" => $course->session_code
        ), '', 'chksum, group_id, group_desc, moodle_id');

        // Map to objects.
        foreach ($data as &$group) {
            $obj = new group();
            $obj->id = $group->group_id;
            $obj->moodle_id = $group->moodle_id;
            $obj->description = $group->group_desc;
            $obj->course = $course;
            $obj->module_delivery_key = $course->module_delivery_key;
            $obj->session_code = $course->session_code;
            $obj->chksum = $group->chksum;

            $group = $obj;
        }

        return $data;
    }


    /**
     * Returns all known groups for a given session code.
     * @param unknown $session_code
     * @return unknown
     */
    public static function get_all($session_code, $sort = '', $limitfrom = 0, $limitnum = 0) {
        global $CONNECTDB;

        // Select all our groups.
        $data = $CONNECTDB->get_records("groups", array(
            "session_code" => $session_code
        ), $sort, 'chksum, group_id, group_desc, module_delivery_key, moodle_id', $limitfrom, $limitnum);

        // Map to objects.
        foreach ($data as &$group) {
            $obj = new group();
            $obj->id = $group->group_id;
            $obj->moodle_id = $group->moodle_id;
            $obj->description = $group->group_desc;
            $obj->module_delivery_key = $group->module_delivery_key;
            $obj->session_code = $session_code;
            $obj->chksum = $group->chksum;

            $group = $obj;
        }

        return $data;
    }


}
