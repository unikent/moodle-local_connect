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
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/group/lib.php');

/**
 * Connect group enrolment container
 */
class group_enrolment extends data
{
    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return 'connect_group_enrolments';
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array("id", "groupid", "userid");
    }

    /**
     * Returns an array of fields that link to other databasepods.
     * fieldname -> classname
     */
    protected static function linked_fields() {
        return array(
            'userid' => '\\local_connect\\user',
            'groupid' => '\\local_connect\\group'
        );
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
     * Grab the connect course object
     */
    public function _get_course() {
        if (!$this->group) {
            return null;
        }

        return $this->group->course;
    }

    /**
     * Sync method
     * @param bool $dry
     * @return bool|int
     */
    public function sync($dry = false) {
        // If our group doesn't exist, or is not in Moodle,
        // we cannot continue.
        if (!$this->is_valid()) {
            return self::STATUS_NONE;
        }

        // Create the enrolment if needed.
        if (!$this->is_in_moodle()) {
            if (!$dry) {
                $this->create_in_moodle();
            }

            return self::STATUS_CREATE;
        }

        return self::STATUS_NONE;
    }

    /**
     * Can this be added to Moodle yet?
     * @return unknown
     */
    public function is_valid() {
        if (!$this->course || !$this->user || !$this->group) {
            return false;
        }

        return $this->course->is_in_moodle() && $this->user->is_in_moodle() && $this->group->is_in_moodle();
    }


    /**
     * Check to see if this exists in Moodle
     * @return unknown
     */
    public function is_in_moodle() {
        if (!$this->is_valid()) {
            return false;
        }

        return groups_is_member($this->group->mid, $this->user->mid);
    }


    /**
     * Create this group enrolment in Moodle
     * @return unknown
     */
    public function create_in_moodle() {
        if (!$this->is_valid()) {
            return false;
        }

        // Is the user enrolled?
        $enrolment = enrolment::get_for_user_and_course($this->user, $this->course);
        if (!$enrolment) {
            return false;
        }

        // Create the enrolment if we need to.
        if (!$enrolment->is_in_moodle()) {
            if (!$enrolment->create_in_moodle()) {
                return false;
            }
        }

        if (!groups_add_member($this->group->mid, $this->user->mid, 'enrol_connect')) {
            $msg = "Failed to enrol '{$this->user->mid}' in group '{$this->group->mid}'. Course: '{$this->course->mid}'";
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
                'groupid' => $this->groupid
            )
        );
        $event = \local_connect\event\group_enrolment_created::create($params);
        $event->trigger();

        return true;
    }

    /**
     * Delete from Moodle
     *
     * @return boolean
     */
    public function delete() {
        if (!$this->group || !$this->group->is_in_moodle()) {
            return false;
        }

        if (!$this->user || !$this->user->is_in_moodle()) {
            return false;
        }

        return groups_remove_member($this->group->mid, $this->user->mid);
    }

    /**
     * Returns all group enrolments for a given course
     *
     * @param  local_connect\course $course A course
     * @return local_connect\enrolment Enrolment object
     */
    public static function get_for_course($course) {
        global $DB;

        $sql = 'SELECT cge.* FROM {connect_group_enrolments} cge
            INNER JOIN {connect_group} cg
                ON cg.id=cge.groupid
            WHERE cg.courseid=:courseid';

        $set = $DB->get_records_sql($sql, array(
            'courseid' => $course->id
        ));

        foreach ($set as &$o) {
            $obj = new group_enrolment();
            $obj->set_data($o);
            $o = $obj;
        }

        return $set;
    }
}
