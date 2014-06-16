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

require_once(dirname(__FILE__) . '/../../../course/lib.php');
require_once(dirname(__FILE__) . '/../../../mod/aspirelists/lib.php');
require_once(dirname(__FILE__) . '/../../../mod/forum/lib.php');

/**
 * Connect courses container.
 */
class course extends data
{
    /** Have we been locked? */
    private $_locked;

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
        return array(
            "id", "mid", "module_delivery_key", "session_code", "module_version",
            "campusid", "module_week_beginning", "module_length", "week_beginning_date",
            "module_title", "module_code", "synopsis", "category", "shortname_ext"
        );
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
        $this->reset_object_cache();

        // Have we changed at all?
        if (!$this->is_merged() && $this->is_locked() && $this->has_changed()) {
            if (!$dry) {
                $this->update_moodle();
            }

            return self::STATUS_MODIFY;
        }

        return self::STATUS_NONE;
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
        // If we are a merged course, we may have more than one module_code.
        $modulecode = $this->module_code;
        if ($this->is_in_moodle()) {
            $courses = static::get_by_moodle_id($this->mid);
            if (count($courses) > 1) {
                $modulecode = array($modulecode);
                foreach ($courses as $course) {
                    $current = $course->module_code;
                    if (!in_array($current, $modulecode)) {
                        $modulecode[] = $current;
                    }
                }

                // Sort and implode.
                sort($modulecode);
                $modulecode = implode('/', $modulecode);
            }
        }

        if (!empty($this->shortname_ext)) {
            return $modulecode . " " . $this->shortname_ext;
        }

        return $modulecode;
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
        $lastyear = date('Y', strtotime('1-1-' . $this->session_code . ' -1 year'));
        return "({$lastyear}/{$this->session_code})";
    }

    /**
     * Returns the duration of this course in the format: "i-i"
     */
    public function _get_duration() {
        return $this->module_week_beginning . '-' . ($this->module_week_beginning + $this->module_length);
    }

    /**
     * Get the name of the campus.
     */
    public function _get_campus_name() {
        global $DB;
        return $DB->get_field('connect_campus', 'name', array(
            'id' => $this->campusid
        ));
    }

    /**
     * Week beginning date
     */
    public function _get_week_beginning_date() {
        $data = $this->get_data();
        return strtotime($data->week_beginning_date);
    }

    /**
     * Week ending date
     */
    public function _get_week_ending_date() {
        return strtotime('+' . $this->module_length . ' weeks', $this->week_beginning_date);
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
     * Get the summary, based on the synopsis
     */
    public function _get_summary() {
        $code = $this->module_code;
        if (strpos($code, " ") !== false) {
            $code = substr($code, 0, strpos($code, " "));
        }

        $more = "<a href='http://www.kent.ac.uk/courses/modulecatalogue/modules/{$code}'>more</a>";

        $text = $this->synopsis;
        if (strlen($text) > 250) {
            $text = substr($text, 0, 247) . "... " . $more;
        }

        // If we are a merged course, we may have more than one campus.
        $campus = $this->campus->name;
        if ($this->is_in_moodle()) {
            $courses = static::get_by_moodle_id($this->mid);
            if (count($courses) > 1) {
                $campus = array($campus);
                foreach ($courses as $course) {
                    $current = $course->campus->name;
                    if (!in_array($current, $campus)) {
                        $campus[] = $current;
                    }
                }

                // Sort and implode.
                sort($campus);
                $campus = implode('/', $campus);
            }
        }

        $text = '<div class="synopsistext">' . strip_tags($text) . '</div>';
        $text .= "&nbsp;<p style='margin-top:10px' class='module_summary_extra_info'>";
        $text .= $campus . ", ";
        $text .= "week " . $this->duration;
        $text .= "</p>";

        return $text;
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
     * Is this course part of a merged set?
     * @return boolean
     */
    public function is_merged() {
        global $DB;
        return $DB->count_records('connect_course', array('mid' => $this->mid)) > 1;
    }

    /**
     * Is this course locked?
     * If it is still locked, it means we can update it at will.
     */
    public function is_locked() {
        global $DB;

        if (empty($this->mid)) {
            return true;
        }

        if (!isset($this->_locked)) {
            $locked = $DB->get_field('connect_course_locks', 'locked', array(
                "mid" => $this->mid
            ));

            $this->_locked = $locked !== 0;
        }

        return $this->_locked;
    }

    /**
     * Returns the Moodle URL for this object.
     */
    public function get_moodle_url() {
        if (empty($this->mid)) {
            return "";
        }

        $url = new \moodle_url("/course/view.php", array("id" => $this->mid));
        return $url->out(false);
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
    public function is_unique_shortname($shortname, $strict = false) {
        global $DB;

        // If in strict mode, we check against connect as well.
        if ($strict) {
            $count = $DB->count_records('connect_course', array(
                "module_code" => $shortname
            ));

            if ($count > 1) {
                return false;
            }
        }

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

        // Courses do not sync unless in strict mode.
        $strict = get_config('local_connect', 'strict_sync');
        if (!$strict) {
            return false;
        }

        return  $course->fullname !== $this->fullname ||
                $course->summary !== $this->summary ||
                $course->category !== $this->category;
    }

    /**
     * Create this course in Moodle
     * @param string $shortnameext (optional)
     * @return boolean
     */
    public function create_in_moodle() {
        global $DB, $USER;

        // Check we have a category.
        if (empty($this->category)) {
            \local_connect\util\helpers::error("No category set for course: '{$this->id}'!");
            return false;
        }

        // Grab shortname.
        $shortname = $this->shortname;

        // Ensure the shortname is unique.
        if (!$this->is_unique_shortname($shortname)) {
            $err = "'{$USER->username}' just tried to push course '{$this->id}' to Moodle but the shortname was not unique.";
            \local_connect\util\helpers::error($err);
            return false;
        }

        // Create the course.
        try {
            $obj = new \stdClass();
            $obj->category = $this->category;
            $obj->shortname = $shortname;
            $obj->fullname = $this->fullname;
            $obj->summary = \core_text::convert($this->summary, 'utf-8', 'utf-8');
            $obj->visible = 0;

            $course = create_course($obj);
            if (!$course) {
                throw new \moodle_exception("Unknown");
            }

            // Update our reference.
            $this->mid = $course->id;
        } catch (\moodle_exception $e) {
            $msg = $e->getMessage();
            $err = "'{$USER->username}' just tried to push course '{$this->id}' to Moodle but '{$msg}'.";
            \local_connect\util\helpers::error($err);
            return false;
        }

        // Save our new mid.
        $this->save();

        // Add in sections.
        $DB->set_field('course_sections', 'name', $this->module_title, array (
            'course' => $this->mid,
            'section' => 0
        ));

        // Add the reading list module to our course if it is based in Canterbury.
        if ($this->campus_name === 'Canterbury') {
            $this->create_reading_list();
        }

        // Add a news forum to the course.
        $this->create_forum();

        // Fire the event.
        $params = array(
            'objectid' => $this->id,
            'courseid' => $this->mid,
            'context' => \context_course::instance($this->mid)
        );
        $event = \local_connect\event\course_created::create($params);
        $event->trigger();

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
    public function add_child($target) {
        // Add a link.
        $target->mid = $this->mid;
        $target->save();

        // Update in Moodle.
        $this->update_moodle();

        // Sync enrolments.
        $target->sync_enrolments();

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

        if (!$this->is_locked()) {
            return false;
        }

        $course = $DB->get_record('course', array(
            'id' => $this->mid
        ));

        // Updates!
        $course->shortname = $this->shortname;
        $course->fullname = $this->fullname;
        $course->category = $this->category;
        $course->summary = \core_text::convert($this->summary, 'utf-8', 'utf-8');

        // Update this course in Moodle.
        update_course($course);

        return true;
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
        $this->delete_enrolments();

        $this->mid = 0;
        $this->save();

        return true;
    }

    /**
     * Delete this course's enrolments.
     */
    public function delete_enrolments() {
        $todo = array_merge($this->enrolments, $this->group_enrolments);
        foreach ($todo as $enrolment) {
            if ($enrolment->is_in_moodle()) {
                $enrolment->delete();
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
     * Syncs group enrollments for this Course
     * @todo Updates/Deletions
     */
    public function sync_group_enrolments() {
        if (!$this->is_in_moodle()) {
            return;
        }

        foreach ($this->group_enrolments as $enrolment) {
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
     * Returns the number of people enrolled in this course.
     */
    public function count_all() {
        global $DB;

        return $DB->count_records("connect_enrolments", array(
            'courseid' => $this->id
        ));
    }

    /**
     * Returns the number of students enrolled in this course.
     */
    public function count_students() {
        global $DB;

        $role = $DB->get_field('connect_role', 'id', array('name' => 'student'));
        return $DB->count_records('connect_enrolments', array(
            'courseid' => $this->id,
            'roleid' => $role
        ));
    }

    /**
     * Returns the number of staff enrolled in this course.
     */
    public function count_staff() {
        return $this->count_all() - $this->count_students();
    }

    /**
     * To String override
     * @return unknown
     */
    public function __toString() {
        return !empty($this->module_title) ? $this->shortname : $this->id;
    }

    /**
     * Get a Connect Course by Moodle ID
     * 
     * @param unknown $id
     * @return unknown
     */
    public static function get_by_moodle_id($id) {
        global $DB;

        // Select a bunch of records.
        $result = $DB->get_records('connect_course', array('mid' => $id));
        if (!$result) {
            return array();
        }

        // Decode various elements.
        foreach ($result as &$datum) {
            $obj = new course();
            $obj->set_class_data($datum);
            $datum = $obj;
        }

        return $result;
    }


    /**
     * Get a Connect Course by Devliery Key and Session Code
     * @param unknown $moduledeliverykey
     * @param unknown $session_code
     * @return unknown
     */
    public static function get_by_uid($moduledeliverykey, $sessioncode) {
        global $DB;

        $data = $DB->get_record('connect_course', array(
            'module_delivery_key' => $moduledeliverykey,
            'session_code' => $sessioncode
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
     * @param boolean raw Should all objects be stdClass?
     * @return array
     */
    public static function get_by_category($categories, $raw = false) {
        global $DB;

        list($sql, $params) = $DB->get_in_or_equal($categories);
        $result = $DB->get_records_sql('SELECT * FROM {connect_course} WHERE category ' . $sql, $params);

        // Decode various elements.
        if (!$raw) {
            foreach ($result as &$datum) {
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
            $obj = self::get($course);
            if (!$obj) {
                $response[] = array(
                    'error_code' => 'does_not_exist',
                    'id' => $course
                );
                continue;
            }

            // Make sure this was in Moodle.
            if (!$obj->is_in_moodle()) {
                $response[] = array(
                    'error_code' => 'not_created_in_moodle',
                    'id' => $course
                );
                continue;
            }

            $obj->delete();
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
            $obj = self::get($course->id);
            if (!$obj) {
                $response[] = array(
                    'error_code' => 'does_not_exist',
                    'id' => $course->id
                );
                continue;
            }

            // Make sure we are unique.
            if (!$obj->is_unique()) {
                $response[] = array(
                    'error_code' => 'duplicate',
                    'id' => $course->id
                );
                continue;
            }

            // Did we specify a shortname extension?
            if (!empty($course->shortnameext)) {
                $obj->shortname_ext = $course->shortnameext;
                $obj->save();
            }

            // Attempt to create in Moodle.
            if (!$obj->create_in_moodle()) {
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

        $primary = $courses[0];
        foreach ($courses as $course) {
            if ($course->is_in_moodle()) {
                $primary = $course;
            }
        }

        $link = new course();
        $link->set_class_data($primary->get_data());
        $link->set_class_data(array(
            'module_code' => $input->code,
            'module_title' => $input->title,
            'synopsis' => $input->synopsis,
            'category' => $input->category,
        ));

        // Create the linked course if it doesnt exist.
        if (!$link->is_in_moodle()) {
            if (!$link->create_in_moodle()) {
                \local_connect\util\helpers::error("Could not create linked course: $link");
            }
        } else {
            $link->update_moodle();
        }

        // Add children.
        foreach ($courses as $child) {
            if (!$link->add_child($child)) {
                \local_connect\util\helpers::error("Could not add child '$child' to course: $link");
            }
        }

        return array();
    }

    /**
     *
     * @param unknown $in
     * @return unknown
     */
    public static function process_unlink($in) {
        foreach ($in as $c) {
            $course = self::get($c);
            // All good!
            if (!$course->unlink()) {
                \local_connect\util\helpers::error("Could not remove child '$course'!");
            }
        }

        return array();
    }
}
