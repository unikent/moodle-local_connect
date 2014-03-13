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
        // Should we be deleting this?
        if ($this->sink_deleted) {
            if ($this->is_in_moodle()) {
                if (!$dry) {
                    $this->delete();
                }

                return "Deleting Course: $this->id";
            }

            return null;
        }

        // Should we be creating this?
        if (!$this->is_in_moodle() && $this->has_unique_shortname()) {
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
    public function __get_campus_name() {
        global $DB;
        return $DB->get_field('connect_role', 'name', array(
            'id' => $this->campus
        ));
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
     * Is this course unique?
     * @return boolean
     */
    public function is_unique() {
        global $DB;
        return $DB->count_records('connect_course', array('module_code' => $this->module_code)) === 1;
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
        return $this->mid !== 0;
    }

    /**
     * Does this course have a unique shortname?
     * @return boolean
     */
    public function has_unique_shortname() {
        global $DB;

        $expected = $this->is_in_moodle() ? 1 : 0;
        return $expected === $DB->count_records('course', array(
            "shortname" => $this->shortname
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
        if (!$this->has_unique_shortname()) {
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

        // Add a link.
        if (!$DB->record_exists('connect_course_links', $data)) {
            $DB->insert_record('connect_course_links', $data);
        }

        $this->sync_enrolments();
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
    }


    /**
     * Delete this course
     * @return unknown
     */
    public function delete() {
        global $DB, $CONNECTDB;

        // Step 0 - If this is a linked course, kill our children.
        if (!empty($this->children)) {
            foreach ($this->children as $child) {
                $course = self::get_course_by_chksum($child);
                $course->state = 1;
                $course->_moodle_id = null;
                $course->parent_id = 0;
                $course->save();
            }
        }

        // Step 1 - Move to the 'removed category'.

        $category = \local_catman\core::get_category();

        $course = $DB->get_record('course', array('id' => $this->mid));

        $course->category = $category->id;
        $course->shortname = date("dmY-His") . "-" . $course->shortname;
        $course->idnumber = date("dmY-His") . "-" . $course->idnumber;

        update_course($course);

        // Step 2 - Update enrolments.

        $CONNECTDB->set_field('enrollments', 'state', 1, array (
                'module_delivery_key' => $this->module_delivery_key,
                'session_code' => $this->session_code
            ));

        $CONNECTDB->set_field('group_enrollments', 'state', 1, array (
                'module_delivery_key' => $this->module_delivery_key,
                'session_code' => $this->session_code
            ));

        // Step 3 - Well we havent errored yet! Finish up.

        $CONNECTDB->set_field('courses', 'state', 1, array (
                'module_delivery_key' => $this->module_delivery_key,
                'session_code' => $this->session_code
            ));

        $CONNECTDB->set_field('courses', 'moodle_id', 0, array (
                'module_delivery_key' => $this->module_delivery_key,
                'session_code' => $this->session_code
            ));

        // Step 4 - Update chksum tracker.
        $DB->set_field('connect_course_chksum', 'chksum', $this->chksum, array (
            'module_delivery_key' => $this->module_delivery_key,
            'session_code' => $this->session_code
        ));

        return true;
    }

    /**
     * Syncs enrollments for this Course
     * @todo Updates/Deletions
     */
    public function sync_enrolments() {
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
        return is_string($this->module_title) ? "{$this->module_title} ({$this->shortname})" : "$this->chksum";
    }


    /**
     * Get a Connect Course by Moodle ID
     * @param unknown $id
     * @return unknown
     */
    public static function get_course($id) {
        global $CONNECTDB;

        // Select a bunch of records
        $data = $CONNECTDB->get_records('courses', array('moodle_id' => $id));
        if (empty($data)) {
            return false;
        }

        // If there are many pick a primary
        if (count($data) > 1) {
            foreach ($data as $datum) {
                if ($datum->link) {
                    return new course($datum);
                }
            }
        }

        if (is_array($data)) {
            $data = reset($data);
        }

        return new course($data);
    }


    /**
     * Get a Connect Course by chksum
     * @param unknown $chksum
     * @return unknown
     */
    public static function get_course_by_chksum($chksum) {
        global $CONNECTDB;

        $data = $CONNECTDB->get_record('courses', array('chksum' => $chksum));
        if (!$data) {
            return false;
        }

        return new course($data);
    }


    /**
     * Get a Connect Course by Devliery Key and Session Code
     * @param unknown $module_delivery_key
     * @param unknown $session_code
     * @return unknown
     */
    public static function get_course_by_uid($module_delivery_key, $session_code) {
        global $CONNECTDB;

        $data = $CONNECTDB->get_record('courses', array(
                'module_delivery_key' => $module_delivery_key,
                'session_code' => $session_code
            ), "*", IGNORE_MULTIPLE);

        if (!$data) {
            return false;
        }

        return new course($data);
    }


    /**
     * Is this user allowed to manage courses?
     * @return boolean
     */
    public static function can_manage() {
        global $DB;

        if (has_capability('moodle/site:config', \context_system::instance())) {
            return true;
        }

        $cats = $DB->get_records('course_categories');

        // Check permissions
        foreach ($cats as $cat) {
            $context = \context_coursecat::instance($cat->id);

            if (has_capability('moodle/category:manage', $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an array of all courses in Connect.
     * This is a little complicated and is due to be simplified using magic methods and
     * other such things.
     *
     * @param array category_restrictions A list of categories we dont want
     * @param boolean obj_form Should all objects be of this class type?
     * @param unknown $category_restrictions (optional)
     * @param unknown $obj_form (optional)
     * @return unknown
     */
    public static function get_courses($category_restrictions = array(), $obj_form = false) {
        global $CFG, $CONNECTDB;

        $sql = "SELECT c1.* FROM {courses} c1 WHERE c1.session_code = :sesscode";

        // Add the category restrictions if there are any.
        if (!empty($category_restrictions)) {
            $inQuery = implode(',', array_fill(0, count($category_restrictions), ':cat_'));
            $sql .= " WHERE c1.category_id IN ({$inQuery})";
        }

        // Also a group by.
        $sql .= ' GROUP BY c1.chksum';

        // Create the parameters.
        $params = array(
            "sesscode" => $CFG->connect->session_code
        );

        // Add all the restrictions in.
        foreach ($category_restrictions as $k => $id) {
            $params["cat_" . ($k + 1)] = $id;
        }

        // Run this massive query.
        $result = $CONNECTDB->get_records_sql($sql, $params);

        // Decode various elements.
        $data = array_map(function($obj) use ($obj_form) {
                global $CONNECTDB;

                if (!empty($obj->children)) {
                    $obj->children = json_decode($obj->children);
                }

                $obj->sink_deleted = $obj->sink_deleted === "1" ? true : false;

                $obj->student_count = intval($obj->student_count);
                $obj->teacher_count = intval($obj->teacher_count);
                $obj->convenor_count = intval($obj->convenor_count);

                if ($obj_form) {
                    $obj = new course($obj);
                }

                return $obj;
        }, $result);

        return $data;
    }


    /**
     *
     * @param unknown $data
     * @return unknown
     */
    public static function disengage_all($data) {
        global $CONNECTDB;
        $response = array();

        foreach ($data->courses as $course) {
            // Try to find the Connect version of the course.
            $connect_course = self::get_course_by_uid($course->module_delivery_key, $course->session_code);
            if (!$connect_course) {
                $response[] = array(
                    'error_code' => 'does_not_exist',
                    'id' => $course
                );
                continue;
            }

            // Make sure this was in Moodle.
            if (($course->state & self::$states['created_in_moodle']) === 0) {
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
     *
     * @param unknown $data
     * @return unknown
     */
    public static function schedule_all($data) {
        global $CONNECTDB;
        $response = array();

        foreach ($data->courses as $course) {
            // Try to find the Connect version of the course.
            $connect_course = self::get_course_by_uid($course->module_delivery_key, $course->session_code);
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
     * 
     * @param unknown $input
     * @return unknown
     */
    public static function process_merge($input) {
        $link_course = array(
            'module_code' => $input->code,
            'module_title' => $input->title,
            'primary_child' => $input->primary_child,
            'synopsis' => $input->synopsis,
            'category_id' => $input->category,
            'state' => self::$states['scheduled'],
            'moodle_id' => null
        );

        $courses = array();
        foreach ($input->link_courses as $lc) {
            $course = self::get_course_by_chksum($lc);
            if ($course) {
                $courses[] = $course;
            } else {
                return array('error_code' => 'invalid_course');
            }
        }

        return self::merge($link_course, $courses);
    }

    public static function merge($link_course, $courses) {
        global $CONNECTDB;

        // Lets make sure the module_code is unique.d
        if ($CONNECTDB->count_records('courses', array('module_code' => $link_course['module_code'])) > 0) {
            return array('error_code' => 'duplicate');
        }

        // Grab a list of all courses that are currently linked courses.
        $linked = array_filter($courses, function($v) {
            return $v->link;
        });

        // Check these are not all linked courses.
        if (count($linked) > 1) {
            return array('error_code' => 'cannot_merge_link_courses');
        }

        // If we only have one linked course, we just add the other merge targets as children.
        if (count($linked) == 1) {
            $link = array_shift($linked);

            // Filter out children.
            $children = array_filter($courses, function($v) use ($link) {
                return ($v->chksum != $link->chksum) && (($v->state & course::$states['unprocessed']) > 0);
            });

            // Create the links.
            foreach ($children as $child) {
                $link->add_child($child);
            }

            return array();
        }

        // How many are already created?
        $already_created = array_filter($courses, function($course) {
            return $course->is_in_moodle();
        });

        // we cant link multiple created courses yet.
        if (count($already_created) > 1) {
            return array('error_code' => 'too_many_created');
        }

        // If only one has been created, ninja its moodle_id
        // this means it'll get ignored by the create bit in the job
        // and just fall through to having its children sorted,
        // im not sure thats right, but its how it is right now.
        if (count($already_created) == 1) {
            $course = array_shift($already_created);
            $link_course['moodle_id'] = $course->moodle_id;
            $link_course['state'] = self::$states['created_in_moodle'];
        }

        // Grab hold of the 'primary' delivery and base our details on that.
        $t = array_filter($courses, function($course) use ($link_course) {
            return $course->chksum == $link_course['primary_child'];
        });
        $primary_child = array_shift($t);

        // Choose a primary child.
        $keys = array_keys($courses);
        $parent = $courses[array_pop($keys)];
        if ($primary_child !== null) {
            $parent = $primary_child;
        } else {
            $canterbury_courses = array_filter($courses, function($course) {
                return $course->campus_desc == 'Canterbury';
            });
            $only_canterbury = array_shift($canterbury_courses);

            if ($only_canterbury !== null) {
                $parent = $only_canterbury;
            }
        }

        // Fix up starts and lengths.
        $link_course['module_week_beginning'] = array_reduce($courses, function($a, $i) {
            return ($i->module_week_beginning < $a) ? $i->module_week_beginning : $a;
        }, '52');

        $link_course['module_length'] = array_reduce($courses, function($a, $i) {
            $l = ($i->module_week_beginning + $i->module_length);
            return $l > $a ? $l : $a;
        }, '0') - $link_course['module_week_beginning'];

        $link_course['week_beginning_date'] = array_reduce($courses, function($a, $i) {
            if (null === $a) {
                $a = $i->week_beginning_date;
            }

            return ($i->week_beginning_date < $a) ? $i->week_beginning_date : $a;
        });

        // Grab two UUIDs.
        $uuid = $CONNECTDB->get_record_sql('SELECT uuid() AS uuid');

        // Create it and join them up.
        $tr = $CONNECTDB->start_delegated_transaction();
        $sql = <<<SQL
          INSERT INTO courses (chksum, module_delivery_key, primary_child
            , link, id_chksum, module_code, module_title, synopsis, category_id
            , session_code, delivery_department, campus, campus_desc
            , module_week_beginning, module_length, moodle_id
            , state, created_at, updated_at, week_beginning_date)
          SELECT ?, ?, ?
            , true, uuid(), ?, ?, ?, ?
            , session_code, delivery_department, campus, campus_desc
            , ?, ?, ?
            , ?, now(), now(), ?
            FROM courses WHERE chksum = ?
SQL;

        $primary_child = $link_course['primary_child'];

        $CONNECTDB->execute($sql, array(
            $uuid->uuid,
            $uuid->uuid,
            is_object($primary_child) ? $primary_child->chksum : $primary_child,
            $link_course['module_code'],
            $link_course['module_title'],
            $link_course['synopsis'],
            $link_course['category_id'],
            $link_course['module_week_beginning'],
            $link_course['module_length'],
            $link_course['moodle_id'],
            $link_course['state'],
            $link_course['week_beginning_date'],
            $parent->chksum
        ));

        foreach ($courses as $course) {
            // Update parents.
            $CONNECTDB->set_field('courses', 'parent_id', $uuid->uuid, array(
                "chksum" => $course->chksum
            ));
        }

        $tr->allow_commit();

        // Find the new course.
        $link = self::get_course_by_chksum($uuid->uuid);

        // Add children.
        foreach ($courses as $child) {
            $link->add_child($child);
        }

        return array();
    }


    /**
     *
     * @param unknown $in_courses
     * @return unknown
     */
    public static function process_unlink($in_courses) {
        global $CONNECTDB;
        $r = array();

        foreach ($in_courses as $c) {
            $course = self::get_course_by_chksum($c);

            // Does it exist?
            if ($course === false) {
                $r[] = array(
                    'error_code' => 'does_not_exist',
                    'id' => $c
                );
                continue;
            }

            if ($course->parent_id == null) {
                $r[] = array(
                    'error_code' => 'not_link_course',
                    'id' => $c
                );
                continue;
            }

            // Was this ever created?
            if ($course->is_in_moodle()) {
                $r[] = array(
                    'error_code' => 'not_created',
                    'id' => $c
                );
                continue;
            }

            // All good!
            $course->unlink();
        }

        return $r;
    }

    /**
     * Process a course unlink
     */
    public function unlink() {
        // Remove this course's enrolments and group enrolments
        $enrolments = $this->enrolments;
        $group_enrolments = $this->group_enrolments;
        $todo = array_merge($enrolments, $group_enrolments);
        foreach ($todo as $enrolment) {
            if ($todo->is_in_moodle()) {
                $todo->delete();
            }
        }

        // Unset parent and state
        $this->_moodle_id = null;
        $this->parent_id = null;
        $this->state = self::$states['unprocessed'];
        $this->save();
    }
}
