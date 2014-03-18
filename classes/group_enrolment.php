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

require_once $CFG->dirroot.'/group/lib.php';

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
        return array("id", "groupid", "user", "deleted");
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
     * Sync method
     */
    public function sync($dry = false) {
        // Should we be deleting this?
        if ($this->deleted) {
            if ($this->is_in_moodle()) {
                if (!$dry) {
                    $this->delete();
                }

                return "Deleting Group Enrolment: $this->id";
            }

            return null;
        }

        // Easy option.
        if (!$this->is_in_moodle()) {
            if (!$dry) {
                $this->create_in_moodle();
            }

            return "Creating Group Enrolment: " . $this->id;
        }
    }

    /**
     * Can this be added to Moodle yet?
     * @return unknown
     */
    public function is_valid() {
        $group = group::get($this->groupid);
        if (!$group || !$group->is_in_moodle()) {
            return false;
        }

        $course = course::get($group->course);
        if (!$course || !$course->is_in_moodle()) {
            return false;
        }

        $user = user::get($this->user);
        if (!$user || !$user->is_in_moodle()) {
            return false;
        }

        return true;
    }


    /**
     * Check to see if this exists in Moodle
     * @return unknown
     */
    public function is_in_moodle() {
        $group = group::get($this->groupid);
        if (!$group || !$group->is_in_moodle()) {
            return false;
        }

        $user = user::get($this->user);
        if (!$user || !$user->is_in_moodle()) {
            return false;
        }

        return groups_is_member($group->mid, $user->mid);
    }


    /**
     * Create this group enrolment in Moodle
     * @return unknown
     */
    public function create_in_moodle() {
        $group = group::get($this->groupid);
        if (!$group || !$group->is_in_moodle()) {
            return false;
        }

        $course = course::get($group->course);
        if (!$course || !$course->is_in_moodle()) {
            return false;
        }

        $user = user::get($this->user);
        if (!$user || !$user->is_in_moodle()) {
            return false;
        }

        // Is the user enrolled?
        $enrolment = enrolment::get_for_user_and_course($user, $course);
        if (!$enrolment->is_in_moodle()) {
            if (!$enrolment->create_in_moodle()) {
                return false;
            }
        }

        return groups_add_member($group->mid, $user->mid);
    }

    /**
     * Delete from Moodle
     * 
     * @return boolean
     */
    public function delete() {
        $group = group::get($this->groupid);
        if (!$group || !$group->is_in_moodle()) {
            return false;
        }

        $user = user::get($this->user);
        if (!$user || !$user->is_in_moodle()) {
            return false;
        }

        return groups_remove_member($group->mid, $user->mid);
    }


    /**
     * Returns a group enrolment, given a group ID and a username.
     * @param unknown $group
     * @return unknown
     */
    public static function get($id) {
        global $DB;

        $ge = $DB->get_record('connect_group_enrolments', array(
            'id' => $id
        ));

        $obj = new group_enrolment();
        $obj->set_class_data($ge);

        return $obj;
    }

    /**
     * Returns all known group enrollments for a given group.
     * @param unknown $group
     * @return unknown
     */
    public static function get_for_group($group) {
        global $DB;

        $set = $DB->get_records('connect_group_enrolments', array(
            'groupid' => $group->id
        ));

        foreach ($set as &$o) {
            $obj = new group_enrolment();
            $obj->set_class_data($o);
            $o = $obj;
        }

        return $set;
    }

    /**
     * Returns all group enrolments for a given course
     * 
     * @param  local_connect_course $course A course
     * @return local_connect_enrolment Enrolment object
     */
    public static function get_for_course($course) {
        global $DB;

        $sql = 'SELECT cge.* FROM {connect_group_enrolments} cge
            INNER JOIN {connect_group} cg
                ON cg.id=cge.groupid
            WHERE cg.course=:course';

        $set = $DB->get_records_sql($sql, array(
            'course' => $course->id
        ));

        foreach ($set as &$o) {
            $obj = new group_enrolment();
            $obj->set_class_data($o);
            $o = $obj;
        }

        return $set;
    }

    /**
     * Returns all group enrolments for a given user.
     */
    public static function get_for_user($user) {
        global $DB;

        $set = $DB->get_records('connect_group_enrolments', array(
            'user' => $user->id
        ));

        foreach ($set as &$o) {
            $obj = new group_enrolment();
            $obj->set_class_data($o);
            $o = $obj;
        }

        return $set;
    }

    /**
     * Returns all known group enrolments .
     * @return unknown
     */
    public static function get_all() {
        global $DB;

        $set = $DB->get_records('connect_group_enrolments');

        foreach ($set as &$o) {
            $obj = new group_enrolment();
            $obj->set_class_data($o);
            $o = $obj;
        }

        return $set;
    }

}
