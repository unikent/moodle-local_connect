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

require_once dirname(__FILE__) . '/../../../course/lib.php';
require_once dirname(__FILE__) . '/../../../mod/aspirelists/lib.php';
require_once dirname(__FILE__) . '/../../../mod/forum/lib.php';

/**
 * Connect courses container.
 * This is all a bit dodgy as "it was the first one" to be moved to PHP so was a bit of a learning experience.
 * I shall re-write this at some point but if you are reading this then I probably never did, so sorry.
 */
class course extends data
{
    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return 'connect_course';
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array("id", "mid", "module_delivery_key", "session_code", "module_version", "campus", "module_week_beginning", "module_length", "week_beginning_date", "module_title", "module_code", "synopsis", "category");
    }

    /**
     * A list of immutable fields for this data object.
     */
    protected static function immutable_fields() {
        return array("id", "module_delivery_key", "session_code");
    }

    /**
     * Here is the big sync method.
     */
    public function sync($dry = false) {
        // Should we be creating this?
        if (!$this->is_in_moodle() && $this->is_unique_shortname($this->shortname)) {
            if (!$dry) {
                $this->create_in_moodle();
            }

            return "Creating Course: $this->id";
        }

        // Have we changed at all?
        if ($this->has_changed()) {
            if (!$dry) {
                $this->update_moodle();
            }

            return "Updating Course: $this->id";
        }
    }

    /**
     * Adds the shortname date if required.
     */
    private function append_date($val) {
        if (preg_match('/\(\d+\/\d+\)/is', $val) === 0) {
            $val .= " {$this->bracket_period}";
        }

        return $val;
    }

    /**
     * Returns the shortname
     */
    public function _get_shortname() {
        return $this->append_date($this->module_code);
    }

    /**
     * Returns the fullname
     */
    public function _get_fullname() {
        return $this->append_date($this->module_title);
    }

    /**
     * Returns the addition to the shortname (e.g. (2013/2014))
     */
    public function _get_bracket_period() {
        $prev_year = date('Y', strtotime('1-1-' . $this->session_code . ' -1 year'));
        return "({$prev_year} / {$this->session_code})";
    }

    /**
     * Returns the duration of this course in the format: "i - i"
     */
    public function _get_duration() {
        return $this->module_week_beginning . ' - ' . ($this->module_week_beginning + $this->module_length);
    }

    /**
     * Get the name of the campus.
     */
    public function _get_campus_name() {
        global $DB;
        return $DB->get_field('connect_role', 'name', array(
            'id' => $this->campus
        ));
    }

    /**
     * Week ending date
     */
    public function _get_week_ending_date() {
        return strtotime('+' . $this->module_length . ' weeks', strtotime($this->week_beginning_date));
    }

    /**
     * Get enrollments for this Course
     */
    public function _get_enrolments() {
        return enrolment::get_for_course($this);
    }

    /**
     * Get group enrollments for this Course
     */
    public function _get_group_enrolments() {
        return group_enrolment::get_for_course($this);
    }

    /**
     * Get groups for this Course
     */
    public function _get_groups() {
        return group::get_for_course($this);
    }

    /**
     * Get children of this course.
     * @return unknown
     */
    public function _get_children() {
        global $DB;

        // Select a bunch of records.
        $sql = 'SELECT cc.* FROM {connect_course_links} ccl
            INNER JOIN {connect_course} cc
                ON cc.id=ccl.child
            WHERE ccl.parent = :parent';
        $data = $DB->get_records_sql($sql, array(
            'parent' => $this->id
        ));

        $courses = array();
        foreach ($data as $datum) {
            $course = new course();
            $course->set_class_data($datum);
            $courses[] = $course;
        }

        return $courses;
    }

    /**
     * Get parent of this course.
     * @return unknown
     */
    public function _get_parent() {
        global $DB;
        $id = $DB->get_field('connect_course_links', 'parent', array('child' => $this->id));
        return self::get($id);
    }

    /**
     * Is this course unique?
     * @return boolean
     */
    public function is_unique() {
        global $DB;
        return $DB->count_records('connect_course', array('module_code' => $this->module_code)) === 1;
    }

    /**
     * Is this a child?
     * @return boolean
     */
    public function is_child() {
        global $DB;
        return $DB->count_records('connect_course_links', array('child' => $this->id)) >= 1;
    }

    /**
     * Is this a parent?
     * @return boolean
     */
    public function is_parent() {
        global $DB;
        return $DB->count_records('connect_course_links', array('parent' => $this->id)) >= 1;
    }

    /**
     * Do we have children?
     * @return boolean
     */
    public function has_children() {
        global $DB;
        return $DB->count_records('connect_course_links', array('parent' => $this->id)) >= 1;
    }

    /**
     * Has this course been created in Moodle?
     * @return boolean
     */
    public function is_in_moodle() {
        return !empty($this->mid);
    }

    /**
     * Does this course have a unique shortname?
     * @return boolean
     */
    public function is_unique_shortname($shortname) {
        global $DB;

        $expected = $this->is_in_moodle() ? 1 : 0;
        return $expected === $DB->count_records('course', array(
            "shortname" => $shortname
        ));
    }


    /**
     * Has this course changed at all?
     * @return boolean
     */
    public function has_changed() {
        global $DB;

        // Cant do this if the course doesnt exist.
        if (!$this->is_in_moodle()) {
            return false;
        }

        // Basically we just need to check: category, shortname, fullname and summary.
        $course = $DB->get_record('course', array(
            'id' => $this->mid
        ), 'id, shortname, fullname, category, summary');

        return $course->fullname !== $this->fullname || $course->category !== $this->category || $course->summary !== $this->synopsis;
    }

    /**
     * Returns connect_course_dets data.
     * @return unknown
     */
    private function get_dets_data() {
        global $CFG, $DB;

        // Try to find an existing set of data.
        $connect_data = $DB->get_record('connect_course_dets', array(
            'course' => $this->mid
        ));

        // Create a data container.
        if (!$connect_data) {
            $connect_data = new \stdClass();
        }

        // Update the container's data.
        $connect_data->course = $this->mid;
        $connect_data->campus = $this->campus_name;
        $connect_data->startdate = $this->week_beginning_date;
        $connect_data->enddate = $this->week_ending_date;
        $connect_data->weeks = $this->module_length;

        return $connect_data;
    }

    /**
     * Add Connect extra details for this course
     */
    private function create_connect_extras() {
        global $DB;

        $connect_data = $this->get_dets_data();

        if (!isset($connect_data->id)) {
            $DB->insert_record('connect_course_dets', $connect_data);
        } else {
            $DB->update_record('connect_course_dets', $connect_data);
        }
    }

    /**
     * Create this course in Moodle
     * @param string $shortname_ext (optional)
     * @return boolean
     */
    public function create_in_moodle($shortname_ext = "") {
        global $DB;

        // Check we have a category.
        if (empty($this->category)) {
            debugging("No category set for course: {$this->id}!\n", DEBUG_DEVELOPER);
            return false;
        }

        // Append shortname extension if it exists.
        $shortname = $this->shortname;
        if (!empty($shortname_ext)) {
            $shortname = $this->append_date($this->module_code . " " . $shortname_ext);
        }

        // Ensure the shortname is unique.
        if (!$this->is_unique_shortname($shortname)) {
            debugging("Shortname '$shortname' must be unique for course: '{$this->id}'", DEBUG_DEVELOPER);
            return false;
        }

        // Create the course.
        try {
            $obj = new \stdClass();
            $obj->category = $this->category;
            $obj->shortname = $shortname;
            $obj->fullname = $this->fullname;
            $obj->summary = $this->synopsis;
            $obj->visible = 0;
            $course = create_course($obj);
            if (!$course) {
                throw new \moodle_exception("Unknown");
            }
        } catch (\moodle_exception $e) {
            $msg = $e->getMessage();
            debugging("Error processing '{$this->id}': $msg", DEBUG_DEVELOPER);
            return false;
        }

        // Update our reference (TODO - handler in observer?).
        $this->mid = $course->id;

        // Tell Connect about the new course.
        $this->save();

        // Add in sections.
        $DB->set_field('course_sections', 'name', $this->module_title, array (
            'course' => $this->mid,
            'section' => 0
        ));

        // Add module extra details to the connect_course_dets table.
        $this->create_connect_extras();

        // Add the reading list module to our course if it is based in Canterbury.
        if ($this->campus_name === 'Canterbury') {
            $this->create_reading_list();
        }

        // Add a news forum to the course.
        $this->create_forum();

        // Sync our enrolments.
        $this->sync_enrolments();

        // Sync our groups.
        $this->sync_groups();

        return true;
    }


    /**
     * Link a course to this course
     * @param unknown $target
     * @return unknown
     */
    private function add_child($target) {
        global $DB;

        $data = array(
            'parent' => $this->id,
            'child' => $target->id
        );

        if ($DB->record_exists('connect_course_links', $data)) {
            return false;
        }

        // Add a link.
        $DB->insert_record('connect_course_links', $data);
        $this->sync_enrolments();

        return true;
    }

    /**
     * Add reading list module to this course
     */
    private function create_reading_list() {
        global $DB;

        $module = $DB->get_record('modules', array(
            'name' => 'aspirelists'
        ));

        // Create a data container.
        $rl = new \stdClass();
        $rl->course     = $this->mid;
        $rl->name       = 'Reading list';
        $rl->intro      = '';
        $rl->introformat  = 1;
        $rl->category     = 'all';
        $rl->timemodified = time();

        // Create the instance.
        $instance = aspirelists_add_instance($rl, new \stdClass());

        // Find the first course section.
        $section = $DB->get_record_sql("SELECT id, sequence FROM {course_sections} WHERE course=:cid AND section=0", array(
            'cid' => $this->mid
        ));

        // Create a module container.
        $cm = new \stdClass();
        $cm->course     = $this->mid;
        $cm->module     = $module->id;
        $cm->instance   = $instance;
        $cm->section    = $section->id;
        $cm->visible    = 1;

        // Create the module.
        $coursemodule = add_course_module($cm);

        // Add it to the section.
        $DB->set_field('course_sections', 'sequence', "$coursemodule,$section->sequence", array(
            'id' => $section->id
        ));
    }


    /**
     * Add a forum module to this course
     */
    private function create_forum() {
        forum_get_course_forum($this->mid, 'news');
    }


    /**
     * Update this course in Moodle
     */
    public function update_moodle() {
        global $DB;

        $course = $DB->get_record('course', array(
            'id' => $this->mid
        ));

        // Updates!
        $course->fullname = $this->fullname;
        $course->category = $this->category;
        $course->summary = $this->synopsis;

        // Update this course in Moodle.
        update_course($course);

        // Add module extra details to the connect_course_dets table.
        $this->create_connect_extras();
    }

    /**
     * Delete this course
     * 
     * @return boolean
     */
    public function delete() {
        global $DB;

        $course = $DB->get_record('course', array(
            'id' => $this->mid
        ));

        // Step 0 - If this is a linked course, kill our children (bit mean really).
        $DB->delete_records('connect_course_links', 'parent', $this->id);

        // Step 1 - Move to the 'removed category'.
        $category = \local_catman\core::get_category();
        $course->category = $category->id;

        // Step 2 - Also update shortname/id.
        $course->shortname = date("dmY-His") . "-" . $course->shortname;
        $course->idnumber = date("dmY-His") . "-" . $course->idnumber;

        // Step 3 - Commit to DB.
        update_course($course);

        // Step 4 - Update this entry (TODO - move to observer?).
        $this->mid = 0;
        $this->save();

        // Step 5 - Delete enrolments.
        $this->delete_enrolments();

        return true;
    }

    /**
     * Process a course unlink
     */
    public function unlink() {
        global $DB;

        $this->delete_enrolments();

        $DB->delete_records('connect_course_links', array(
            'child' => $this->id
        ));
    }

    /**
     * Delete this course's enrolments.
     */
    public function delete_enrolments() {
        $enrolments = $this->enrolments;
        $group_enrolments = $this->group_enrolments;
        $todo = array_merge($enrolments, $group_enrolments);
        foreach ($todo as $enrolment) {
            if ($todo->is_in_moodle()) {
                $todo->delete();
            }
        }
    }

    /**
     * Syncs enrollments for this Course
     * @todo Updates/Deletions
     */
    public function sync_enrolments() {
        if (!$this->is_in_moodle()) {
            return;
        }

        foreach ($this->enrolments as $enrolment) {
            if (!$enrolment->is_in_moodle()) {
                $enrolment->create_in_moodle();
            }
        }
    }

    /**
     * Syncs groups for this Course
     * @todo Updates/Deletions
     */
    public function sync_groups() {
        if (!$this->is_in_moodle()) {
            return;
        }

        foreach ($this->groups as $group) {
            if (!$group->is_in_moodle()) {
                $group->create_in_moodle();
            }
        }
    }

    /**
     * To String override
     * @return unknown
     */
    public function __toString() {
        return !empty($this->module_title) ? $this->shortname : $this->id;
    }

    /**
     * Get a Connect Course by ID
     * @param unknown $id
     * @return unknown
     */
    public static function get($id) {
        global $DB;

        // Select a bunch of records
        $data = $DB->get_record('connect_course', array('id' => $id));
        if (!$data) {
            return false;
        }

        $course = new course();
        $course->set_class_data($data);
        return $course;
    }

    /**
     * Get a Connect Course by Moodle ID
     * @param unknown $id
     * @return unknown
     */
    public static function get_by_moodle_id($id) {
        global $DB;

        // Select a bunch of records
        $data = $DB->get_record('connect_course', array('mid' => $id));
        if (!$data) {
            return false;
        }

        $course = new course();
        $course->set_class_data($data);
        return $course;
    }


    /**
     * Get a Connect Course by Devliery Key and Session Code
     * @param unknown $module_delivery_key
     * @param unknown $session_code
     * @return unknown
     */
    public static function get_by_uid($module_delivery_key, $session_code) {
        global $DB;

        $data = $DB->get_record('connect_course', array(
            'module_delivery_key' => $module_delivery_key,
            'session_code' => $session_code
        ), "*");

        if (!$data) {
            return false;
        }

        $course = new course();
        $course->set_class_data($data);
        return $course;
    }

    /**
     * Returns an array of all courses in Connect.
     * This is a little complicated and is due to be simplified using magic methods and
     * other such things.
     *
     * @param array category_restrictions A list of categories we dont want
     * @param boolean obj_form Should all objects be of this class type?
     * @param array $category_restrictions (optional)
     * @param boolean $obj_form (optional)
     * @return array
     */
    public static function get_all($category_restrictions = array(), $obj_form = true) {
        global $DB;

        $params = array();
        if (!empty($category_restrictions)) {
            $params['category'] = $category_restrictions;
        }

        $result = $DB->get_records('connect_course', $params);

        // Decode various elements.
        foreach ($result as &$datum) {
            if ($obj_form) {
                $obj = new course();
                $obj->set_class_data($datum);
                $datum = $obj;
            }
        }

        return $result;
    }


    /**
     * Disenguage a list of courses.
     * This is a hangover from the old UI.
     * 
     * @param unknown $data
     * @return unknown
     */
    public static function disengage_all($data) {
        $response = array();

        foreach ($data->courses as $course) {
            // Try to find the Connect version of the course.
            $connect_course = self::get_by_uid($course->module_delivery_key, $course->session_code);
            if (!$connect_course) {
                $response[] = array(
                    'error_code' => 'does_not_exist',
                    'id' => $course
                );
                continue;
            }

            // Make sure this was in Moodle.
            if (!$course->is_in_moodle()) {
                $response[] = array(
                    'error_code' => 'not_created_in_moodle',
                    'id' => $course
                );
                continue;
            }

            $connect_course->delete();
        }

        return $response;
    }

    /**
     * Schedule a group of courses.
     * This is a hangover from the old UI.
     * 
     * @param unknown $data
     * @return unknown
     */
    public static function schedule_all($data) {
        $response = array();

        foreach ($data->courses as $course) {
            // Try to find the Connect version of the course.
            $connect_course = self::get_by_uid($course->module_delivery_key, $course->session_code);
            if (!$connect_course) {
                $response[] = array(
                    'error_code' => 'does_not_exist',
                    'id' => $course->id
                );
                continue;
            }

            // Make sure we are unique.
            if (!$connect_course->is_unique()) {
                $response[] = array(
                    'error_code' => 'duplicate',
                    'id' => $course->id
                );
                continue;
            }

            // Did we specify a shortname extension?
            $shortname_ext = isset($course->shortname_ext) ? $course->shortname_ext : "";

            // Attempt to create in Moodle.
            if (!$connect_course->create_in_moodle($shortname_ext)) {
                $response[] = array(
                    'error_code' => 'error',
                    'id' => $course->id
                );
            }
        }

        return $response;
    }

    /**
     * Merge two courses.
     * Hangover from old UI.
     * 
     * @param unknown $input
     * @return unknown
     */
    public static function process_merge($input) {
        $courses = array();
        foreach ($input->link_courses as $lc) {
            $course = self::get($lc);
            if ($course) {
                $courses[] = $course;
            } else {
                return array('error_code' => 'invalid_course');
            }
        }

        $primary_child = $courses[0];

        $link_course = new course();
        $link_course->set_class_data($primary_child->get_data());
        $link_course->set_class_data(array(
            'module_code' => $input->code,
            'module_title' => $input->title,
            'synopsis' => $input->synopsis,
            'category' => $input->category,
        ));

        // Create the linked course if it doesnt exist.
        if (!$link_course->is_in_moodle()) {
            if (!$link_course->create_in_moodle()) {
                debugging("Could not create linked course: $link_course");
            }
        }

        // Add children.
        foreach ($courses as $child) {
            if (!$link_course->add_child($child)) {
                debugging("Could not add child '$child' to course: $link_course");
            }
        }

        return $link_course;
    }

    /**
     *
     * @param unknown $in_courses
     * @return unknown
     */
    public static function process_unlink($in_courses) {
        foreach ($in_courses as $c) {
            $course = self::get($c);
            // All good!
            if (!$course->unlink()) {
                debugging("Could not remove child '$course'!");
            }
        }

        return array();
    }
}
