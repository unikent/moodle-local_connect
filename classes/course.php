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
 * @package    core_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect;

defined('MOODLE_INTERNAL') || die();

/**
 * Connect courses container
 */
class course {
    /** Our chksum */
    private $chksum;

    /** Our state */
    private $state;

    /** Our module code */
    private $module_code;

    /** Our module title */
    private $module_title;

    /** Our module version */
    private $module_version;

    /** Our campus */
    private $campus;

    /** Our campus desc */
    private $campus_desc;

    /** Our synopsis */
    private $synopsis;

    /** Our module week beginning */
    private $module_week_beginning;

    /** Our module length */
    private $module_length;

    /** Our moodle id */
    private $moodle_id;

    /** Our sink deleted */
    private $sink_deleted;

    /** Our student count */
    private $student_count;

    /** Our teacher count */
    private $teacher_count;

    /** Our convenor_count */
    private $convenor_count;

    /** Our parent id */
    private $parent_id;

    /** Our session code */
    private $session_code;

    /** Our category id */
    private $category_id;

    /** Our delivery department */
    private $delivery_department;

    /** Our children */
    private $children;

    /**
     * Constructor to build from a database object
     */
    public function __construct($obj) {
        $this->chksum = $obj->chksum;
        $this->state = $obj->state;
        $this->module_code = $obj->module_code;
        $this->module_title = $obj->module_title;
        $this->module_version = $obj->module_version;
        $this->campus = $obj->campus;
        $this->campus_desc = $obj->campus_desc;
        $this->synopsis = $obj->synopsis;
        $this->module_week_beginning = $obj->module_week_beginning;
        $this->module_length = $obj->module_length;
        $this->moodle_id = $obj->moodle_id;
        $this->sink_deleted = $obj->sink_deleted;
        $this->student_count = $obj->student_count;
        $this->teacher_count = $obj->teacher_count;
        $this->convenor_count = $obj->convenor_count;
        $this->parent_id = $obj->parent_id;
        $this->session_code = $obj->session_code;
        $this->category_id = $obj->category_id;
        $this->delivery_department = $obj->delivery_department;
        $this->children = $obj->children;
    }

    /**
     * Accessor method
     */
    public function get_module_code() {
        return $this->module_code;
    }

    /**
     * Accessor method
     */
    public function set_module_code($value) {
        $this->module_code = $value;
    }

    /**
     * Accessor method
     */
    public function get_module_title() {
        return $this->module_title;
    }

    /**
     * Accessor method
     */
    public function set_module_title($value) {
        $this->module_title = $value;
    }

    /**
     * Accessor method
     */
    public function get_synopsis() {
        return $this->synopsis;
    }

    /**
     * Accessor method
     */
    public function set_synopsis($value) {
        $this->synopsis = $value;
    }

    /**
     * Accessor method
     */
    public function get_category_id() {
        return $this->category_id;
    }

    /**
     * Accessor method
     */
    public function set_category_id($value) {
        $this->category_id = $value;
    }

    /**
     * Accessor method
     */
    public function get_chksum() {
        return $this->chksum;
    }

    /**
     * Update this course
     */
    public function update() {
        global $CONNECTDB;

        $sql = "UPDATE courses SET
                    module_code = :module_code,
                    module_title = :module_title,
                    synopsis = :synopsis,
                    category_id = :category_id,
                    state = :state,
                WHERE chksum = :chksum";

        $params = array(
            "module_code" => $this->module_code,
            "module_title" => $this->module_title,
            "synopsis" => $this->synopsis,
            "category_id" => $this->category_id,
            "state" => $this->state,
            "chksum" => $this->chksum
        );

        return $CONNECTDB->execute($sql, $params);
    }

    /**
     * Is this course unique?
     */
    public function is_unique() {
        global $CONNECTDB;

        $sql = "SELECT COUNT(*) as count FROM courses
                  WHERE session_code = ?
                    AND module_code = ?
                    AND chksum != ?
                    AND state IN (2, 4, 6, 8, 10, 12)";

        $params = array(
            $this->session_code,
            $this->module_code,
            $this->chksum
        );

        return $CONNECTDB->count_records_sql($sql, $params) > 0;
    }

    /**
     * Has this course been scheduled for rollover?
     */
    public function is_scheduled() {
        return in_array($this->state, array(2, 4, 6, 8, 10, 12));
    }

    /**
     * Has this course been created in Moodle?
     */
    public function is_created() {
        return $this->moodle_id != 0;
    }

    /**
     * Create this course in Moodle
     */
    public function create() {

    }

    /**
     * Update this course in Moodle
     * @todo
     */
    public function update() {

    }

    /**
     * To String override
     */
    public function __toString() {
        return $this->module_title;
    }

    /**
     * Get a course by chksum
     */
    public static function get_course($chksum) {
        global $CONNECTDB;
        return $CONNECTDB->get_record('courses', array('chksum' => $courseid));
    }

    /**
     * Is this user allowed to grab a list of courses?
     */
    public static function has_access() {
        global $DB;

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
     * Returns an array of all courses in Connect
     *
     * @param array category_restrictions A list of categories we dont want
     * @param boolean obj_form Should all objects be of this class type?
     */
    public static function get_courses($category_restrictions = array(), $obj_form = false) {
        global $CONNECTDB;

        // Set up our various variables.
        $cache = \cache::make('local_connect', 'kent_connect');
        $data = array();

        // Cache in MUC.
        $cache_key = "local_connect_course::get_courses." . implode('.', $category_restrictions) . ($obj_form ? ".obj" : ".std");
        $cache_content = $cache->get($cache_key);
        if ($cache_content !== false) {
            return $cache_content;
        }

        $sql = "SELECT 
                    c1.chksum,
                    CONCAT('[',COALESCE(GROUP_CONCAT(CONCAT('\"',statecode.state,'\"')),''),']') state,
                    c1.module_code,
                    c1.module_title,
                    c1.module_version,
                    c1.campus,
                    c1.campus_desc,
                    c1.synopsis,
                    c1.module_week_beginning,
                    c1.module_length,
                    c1.moodle_id,
                    c1.sink_deleted,
                    c1.student_count,
                    c1.teacher_count,
                    c1.convenor_count,
                    c1.parent_id,
                    c1.session_code,
                    c1.category_id,
                    c1.delivery_department,
                    CONCAT('[',COALESCE(GROUP_CONCAT(CONCAT('\"',c2.chksum,'\"')),''),']') children
                  FROM courses c1
                    LEFT OUTER JOIN courses c2
                        ON c1.module_delivery_key = c2.parent_id
                    LEFT OUTER JOIN (
                                        SELECT 'unprocessed' state, 1 code
                                      UNION
                                        SELECT 'scheduled' state, 2 code
                                      UNION
                                        SELECT 'processing' state, 4 code
                                      UNION
                                        SELECT 'created_in_moodle' state, 8 code
                                      UNION
                                        SELECT 'failed_in_moodle' state, 16 code
                                      UNION
                                        SELECT 'disengage' state, 32 code
                                      UNION
                                        SELECT 'disengaged_from_moodle' state, 64 code
                                    ) statecode
                        ON (c1.state & statecode.code) > 0";

        // Add the category restrictions if there are any.
        if (!empty($category_restrictions)) {
          $inQuery = implode(',', array_fill(0, count($category_restrictions), ':cat_'));
          $sql .= " WHERE c1.category_id IN ({$inQuery})";
        }

        // Also a group by.
        $sql .= ' GROUP BY c1.chksum';

        // Create the parameters.
        $params = array();

        // Add all the restrictions in.
        foreach ($category_restrictions as $k => $id) {
            $params["cat_" . ($k + 1)] = $id;
        }

        // Run this massive query.
        $result = $CONNECTDB->get_records_sql($sql, $params);

        // Decode various elements.
        $data = array_map(function($obj) use ($obj_form) {
            if (!empty($obj->children)) {
                $obj->children = json_decode($obj->children);
            }

            if (!empty($obj->state)) {
                $obj->state = json_decode($obj->state);
            }

            if ($obj_form) {
                $obj = new course($obj);
            }

            return $obj;
        }, $result);

        // Set the MUC cache.
        $cache->set($cache_key, $data);

        return $data;
    }
}