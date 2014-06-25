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
 * Connect meta container
 */
class meta extends data
{
    const OBJECT_TYPE_CATEGORY = 1;
    const OBJECT_TYPE_COURSE = 2;
    const OBJECT_TYPE_GROUP = 3;
    const OBJECT_TYPE_ROLE = 4;

    /** Course object cache */
    private $course;

    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return "connect_meta";
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array("id", "objectid", "objecttype", "courseid");
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
     * Sync up.
     */
    public function sync($dry = false) {
        $context = \context_course::instance($this->courseid, IGNORE_MISSING);
        if ($context === false) {
            return self::STATUS_ERROR;
        }

        foreach ($this->enrolments as $enrolment) {
            if ($this->objecttype == self::OBJECT_TYPE_GROUP) {
                // This is a group enrolment.. grr...
                // Map it to a real enrolment.
                $enrolment = enrolment::get_for_user_and_course($enrolment->user, $enrolment->group->course);
            }

            $uid = $enrolment->user->mid;

            if ($uid && !is_enrolled($context, $uid)) {
                if (!$dry) {
                    enrol_try_internal_enrol($this->courseid, $uid, $enrolment->role->mid);
                }

                return self::STATUS_CREATE;
            }
        }

        return self::STATUS_NONE;
    }

    /**
     * Returns the object for this meta set.
     */
    public function _get_object() {
        $object = null;
        switch ($this->objecttype) {
            case self::OBJECT_TYPE_CATEGORY:
                $object = category::get($this->objectid);
            break;
            case self::OBJECT_TYPE_COURSE:
                $object = course::get($this->objectid);
            break;
            case self::OBJECT_TYPE_GROUP:
                $object = group::get($this->objectid);
            break;
            case self::OBJECT_TYPE_ROLE:
                $object = role::get($this->objectid);
            break;
        }
        return $object;
    }

    /**
     * The course is a Moodle course here.
     */
    public function _get_course() {
        global $DB;
        if (!isset($this->course)) {
            $this->course = $DB->get_record('course', array('id' => $this->courseid));
        }
        return $this->course;
    }

    /**
     * Returns an array of enrolments for this meta set.
     */
    public function _get_enrolments() {
        return $this->object->enrolments;
    }

    /**
     * Is this meta set in Moodle?
     * @return boolean
     */
    public function is_in_moodle() {
        return false;
    }

    /**
     * Create this meta set in Moodle.
     */
    public function create_in_moodle() {
        return false;
    }

    /**
     * Delete this meta set from Moodle
     */
    public function delete() {
        return false;
    }

    /**
     * To string method
     */
    public function __toString() {
        $str = '';
        switch ($this->objecttype) {
            case self::OBJECT_TYPE_CATEGORY:
                $str .= "Category";
            break;
            case self::OBJECT_TYPE_COURSE:
                $str .= "Course";
            break;
            case self::OBJECT_TYPE_GROUP:
                $str .= "Group";
            break;
            case self::OBJECT_TYPE_ROLE:
                $str .= "Role";
            break;
        }
        return "{$str} ({$this->objectid})";
    }
}