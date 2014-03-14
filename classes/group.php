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
    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return 'connect_group';
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array("id", "mid", "course", "name");
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
     * The big sync method.
     */
    public function sync($dry = false) {
        global $DB;

        // The easiest path!
        if (!$this->is_in_moodle()) {
            if (!$dry) {
                $this->create_in_moodle();
            }

            return 'Creating group: ' . $this->id;
        }

        // We are currently in Moodle!
        $group = $DB->get_record('groups', array(
            'id' => $this->mid
        ), 'id,courseid,name');

        // Does our data match up?
        if ($group->name !== $this->name) {
            if (!$dry) {
                $group->name = $this->name;
                groups_update_group($group);
            }

            return 'Updating group: ' . $this->id;
        }
    }

    /**
     * Grab (or create) our grouping ID
     * @return unknown
     */
    private function get_or_create_grouping() {
        global $DB;

        $course = course::get($this->course);

        $grouping = $DB->get_record('groupings', array(
            'name' => 'Seminar groups',
            'courseid' => $this->mid
        ));

        // Create?
        if (!$grouping) {
            $data = new \stdClass();
            $data->name = "Seminar groups";
            $data->courseid = $course->mid;
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
        return !empty($this->mid);
    }


    /**
     * Create this group in Moodle
     * @return unknown
     */
    public function create_in_moodle() {
        global $CFG;

        $course = course::get($this->course);

        if (!$course->is_in_moodle()) {
            debugging("Attempting to create group '{$this->id}' but course '{$this->course}' doesnt exist!", DEBUG_DEVELOPER);
            return false;
        }

        require_once $CFG->dirroot . '/group/lib.php';

        $data = new \stdClass();
        $data->name = $this->name;
        $data->courseid = $course->mid;
        $data->description = '';

        // Grab a Moodle ID.
        $this->mid = groups_create_group($data);
        if ($this->mid === false) {
            debugging("Failed attempting to create group '{$this->id}'!", DEBUG_DEVELOPER);
            return false;
        }

        // Save the Moodle ID to DB.
        $this->save();

        // Grab our grouping.
        $grouping_id = $this->get_or_create_grouping();

        // And add this group to the grouping.
        groups_assign_grouping($grouping_id, $this->mid);

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
     * Returns the number of people enrolled in this group.
     */
    public function count_all() {
        global $DB;
        return $DB->count_records("connect_group_enrolments", array(
            "groupid" => $this->id
        ));
    }

    /**
     * Returns the number of students enrolled in this group.
     */
    public function count_students() {
        global $DB;

        $role = $DB->get_field('connect_role', 'id', array('name' => 'student'));

        $sql = "SELECT COUNT(cge.id) as count
        FROM {connect_group_enrolments} cge
        INNER JOIN {connect_enrolments} ce
            ON ce.user=cge.user
        WHERE cge.groupid=:id AND ce.course=:course AND ce.role=:role";

        return $DB->count_records_sql($sql, array(
            "id" => $this->id,
            "course" => $this->course,
            "role" => $role
        ));
    }

    /**
     * Returns the number of staff enrolled in this group.
     */
    public function count_staff() {
        return $this->count_all() - $this->count_students();
    }

    /**
     * Returns a group specified by ID
     * @param unknown $id
     * @return unknown
     */
    public static function get($id) {
        global $DB;

        $group = $DB->get_record('connect_group', array(
            'id' => $id
        ));

        $obj = new group();
        $obj->set_class_data($group);

        return $obj;
    }


    /**
     * Returns all known groups for a given course.
     * @param unknown $course
     * @return unknown
     */
    public static function get_for_course($course) {
        global $DB;

        // Select all our groups.
        $data = $DB->get_records('connect_group', array(
            'course' => $course->id
        ));

        // Map to objects.
        foreach ($data as &$group) {
            $obj = new group();
            $obj->set_class_data($group);
            $group = $obj;
        }

        return $data;
    }


    /**
     * Returns all known groups for a given session code.
     * @param unknown $session_code
     * @return unknown
     */
    public static function get_all($sort = '', $limitfrom = 0, $limitnum = 0) {
        global $DB;

        // Select all our groups.
        $data = $DB->get_records('connect_group', array(), $sort, '*', $limitfrom, $limitnum);

        // Map to objects.
        foreach ($data as &$group) {
            $obj = new group();
            $obj->set_class_data($group);

            $group = $obj;
        }

        return $data;
    }


}
