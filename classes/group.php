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
class group {

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
     * Grab our Connect Course
     */
    private function get_course() {
    	if (isset($this->course)) {
    		return $this->course;
    	}

    	$this->course = course::get_course_by_uid($this->module_delivery_key, $this->session_code);

    	return $this->course;
    }

    /**
     * Grab our Moodle ID
     */
    public function get_moodle_id() {
        global $DB;

        if (empty($this->moodle_id)) {
            $course = $this->get_course();
            $course_moodle_id = $course->moodle_id;

            $group = $DB->get_record('groups', array(
                "courseid" => $course_moodle_id,
                "name" => $this->description
            ));

            $this->moodle_id = $group->id;
        }

        return $this->moodle_id;
    }

    /**
     * Grab (or create) our grouping ID
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
     */
    public function is_in_moodle() {
    	global $DB;

    	$course = $this->get_course();
    	$course_moodle_id = $course->moodle_id;

        $sql = "SELECT COUNT(g.id) FROM {groups} g WHERE g.courseid=:courseid AND g.name=:name";
        $params = array(
        	"courseid" => $course_moodle_id,
        	"name" => $this->description
        );

        return $DB->count_records_sql($sql, $params) > 0;
    }

    /**
     * Create this group in Moodle
     */
    public function create_in_moodle() {
    	global $CFG;

    	$course = $this->get_course();

    	if (empty($course->moodle_id)) {
    		return false;
    	}

		require_once ($CFG->dirroot . '/group/lib.php');

    	$data = new \stdClass();
    	$data->name = $this->description;
		$data->courseid = $course->moodle_id;
		$data->description = '';

    	$this->moodle_id = groups_create_group($data);

		if ($this->moodle_id === false) {
			return false;
		}

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
     * Returns a group specified by ID
     */
    public static function get($uid) {
        global $CONNECTDB;

        $group = $CONNECTDB->get_record('groups', array(
            'group_id' => $uid
        ));

        $obj = new group();
        $obj->id = $uid;
        $obj->moodle_id = $group->moodle_id;
        $obj->description = $group->group_desc;
        $obj->module_delivery_key = $group->module_delivery_key;
        $obj->session_code = $group->session_code;

        return $obj;
    }

    /**
     * Returns all known groups for a given course.
     */
    public static function get_for_course($course) {
        global $CONNECTDB;

        // Select all our groups.
        $sql = "SELECT g.group_id id, g.group_desc description, g.moodle_id
        			FROM `groups` g
                WHERE g.module_delivery_key=:deliverykey AND g.session_code = :sessioncode";

        $data = $CONNECTDB->get_records_sql($sql, array(
            "deliverykey" => $course->module_delivery_key,
            "sessioncode" => $course->session_code
        ));

        // Map to objects.
        foreach ($data as &$group) {
        	$obj = new group();
        	$obj->id = $group->id;
        	$obj->moodle_id = $group->moodle_id;
        	$obj->description = $group->description;
        	$obj->course = $course;
        	$obj->module_delivery_key = $course->module_delivery_key;
        	$obj->session_code = $course->session_code;

        	$group = $obj;
        }

        return $data;
    }

    /**
     * Returns all known groups for a given session code.
     */
    public static function get_all($session_code) {
        global $CONNECTDB;

        // Select all our groups.
        $sql = "SELECT g.group_id id, g.group_desc description, g.module_delivery_key, g.moodle_id
        			FROM `groups` g
                WHERE g.session_code = :sessioncode";

        $data = $CONNECTDB->get_records_sql($sql, array(
            "sessioncode" => $session_code
        ));

        // Map to objects.
        foreach ($data as &$group) {
        	$obj = new group();
        	$obj->id = $group->id;
        	$obj->moodle_id = $group->moodle_id;
        	$obj->description = $group->description;
        	$obj->module_delivery_key = $group->module_delivery_key;
        	$obj->session_code = $session_code;

        	$group = $obj;
        }

        return $data;
    }
}