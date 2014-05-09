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

require_once($CFG->dirroot . '/group/lib.php');

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
        return array("id", "mid", "courseid", "name");
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
     * Get enrollments for this Group
     */
    public function _get_enrolments() {
        return group_enrolment::get_for_group($this);
    }

    /**
     * The big sync method.
     */
    public function sync($dry = false) {
        global $DB;

        $this->reset_object_cache();

        // Is our course in Moodle?
        if (!$this->course->is_in_moodle()) {
            if (!empty($this->mid)) {
                $this->mid = 0;
                $this->save();

                return self::STATUS_MODIFY;
            }

            return self::STATUS_NONE;
        }

        $status = self::STATUS_NONE;

        // On sync we can be a bit slower... check the mid is valid.
        if ($this->is_in_moodle()) {
            // Do we already *actually* exist?
            if (!$DB->record_exists('groups', array('id' => $this->mid))) {
                $this->mid = 0;
                $this->save();
                $status = self::STATUS_MODIFY;
            }
        }

        // The easiest path!
        if (!$this->is_in_moodle()) {
            if (!$dry) {
                $this->create_in_moodle();
            }

            return self::STATUS_CREATE;
        }

        // We are currently in Moodle!
        $group = $DB->get_record('groups', array(
            'id' => $this->mid
        ), 'id,courseid,name');

        // Does our data match up?
        if ($group->name !== $this->name) {
            if (!$dry) {
                $this->update_in_moodle();
            }

            return self::STATUS_MODIFY;
        }

        return $status;
    }

    /**
     * Grab (or create) our grouping ID
     * @return unknown
     */
    public function get_or_create_grouping($name = 'Seminar groups') {
        global $DB;

        $grouping = $DB->get_record('groupings', array(
            'name' => $name,
            'courseid' => $this->mid
        ), 'id', IGNORE_MULTIPLE);

        // Create?
        if (!$grouping) {
            $data = new \stdClass();
            $data->name = $name;
            $data->courseid = $this->course->mid;
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
     * Returns the Moodle URL of this group
     */
    public function get_moodle_url() {
        if (empty($this->mid)) {
            return "";
        }

        $url = new \moodle_url("/group/index.php", array("id" => $this->course->mid, "group" => $this->mid));
        return $url->out(false);
    }

    /**
     * Create this group in Moodle
     * @return unknown
     */
    public function create_in_moodle($name = 'Seminar groups') {
        global $DB;

        if (!empty($this->mid)) {
            return false;
        }

        if (!$this->course->is_in_moodle()) {
            utils::error("Attempting to create group '{$this->id}' but course '{$this->courseid}' doesnt exist!");
            return false;
        }

        $data = new \stdClass();
        $data->name = $this->name;
        $data->courseid = $this->course->mid;

        // Do we already *actually* exist?
        if ($group = $DB->get_record('groups', (array)$data)) {
            // Yep, link up.
            $this->mid = $group->id;
            $this->save();

            $this->sync_group_enrolments();

            return true;
        }

        // Grab a Moodle ID.
        $data->description = '';
        $this->mid = groups_create_group($data);
        if ($this->mid === false) {
            utils::error("Failed attempting to create group '{$this->id}'. I don't know why :'(");
            return false;
        }

        // Save the Moodle ID to DB.
        $this->save();

        // Grab our grouping.
        $groupingid = $this->get_or_create_grouping($name);

        // And add this group to the grouping.
        groups_assign_grouping($groupingid, $this->mid);

        // Fire the event.
        $params = array(
            'objectid' => $this->id,
            'courseid' => $this->course->mid,
            'context' => \context_course::instance($this->course->mid)
        );
        $event = \local_connect\event\group_created::create($params);
        $event->trigger();

        // Sync enrolments.
        $this->sync_group_enrolments();

        return true;
    }

    /**
     * Update this group in Moodle
     * @return unknown
     */
    public function update_in_moodle() {
        global $DB;

        $group = $DB->get_record('groups', array(
            'id' => $this->mid
        ), 'id,courseid,name');

        // Does our data match up?
        if ($group->name !== $this->name) {
            $group->name = $this->name;
            return groups_update_group($group);
        }

        return true;
    }

    /**
     * Delete this group.
     */
    public function delete() {
        if ($this->is_in_moodle()) {
            if (groups_delete_group($this->mid)) {
                $this->mid = 0;
                $this->save();
            }
        }

        return false;
    }


    /**
     * Syncs group enrollments for this Group
     * @todo Updates/Deletions
     */
    public function sync_group_enrolments() {
        foreach ($this->enrolments as $enrolment) {
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
            ON ce.userid=cge.userid
        WHERE cge.groupid=:id AND ce.courseid=:courseid AND ce.roleid=:roleid";

        return $DB->count_records_sql($sql, array(
            "id" => $this->id,
            "courseid" => $this->courseid,
            "roleid" => $role
        ));
    }

    /**
     * Returns the number of staff enrolled in this group.
     */
    public function count_staff() {
        return $this->count_all() - $this->count_students();
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
            'courseid' => $course->id
        ));

        // Map to objects.
        foreach ($data as &$group) {
            $obj = new group();
            $obj->set_class_data($group);
            $group = $obj;
        }

        return $data;
    }
}
