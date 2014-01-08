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

require_once (dirname(__FILE__) . '/../../../course/lib.php');
require_once (dirname(__FILE__) . '/../../../mod/aspirelists/lib.php');
require_once (dirname(__FILE__) . '/../../../mod/forum/lib.php');

/**
 * Connect courses container
 */
class course {
    /** Our chksum */
    public $chksum;

    /** Our state */
    public $state;

    /** Our module code */
    public $module_code;

    /** Our module title */
    public $module_title;

    /** Our module version */
    public $module_version;

    /** Our campus */
    public $campus;

    /** Our campus desc */
    public $campus_desc;

    /** Our synopsis */
    public $synopsis;

    /** Our module week beginning */
    public $module_week_beginning;

    /** Our module length */
    public $module_length;

    /** Our moodle id */
    public $moodle_id;

    /** Our sink deleted */
    public $sink_deleted;

    /** Our student count */
    public $student_count;

    /** Our teacher count */
    public $teacher_count;

    /** Our convenor_count */
    public $convenor_count;

    /** Our parent id */
    public $parent_id;

    /** Our session code */
    public $session_code;

    /** Our category id */
    public $category;

    /** Our delivery department */
    public $delivery_department;

    /** Our children */
    public $children;

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
        $this->category = $obj->category_id;
        $this->delivery_department = $obj->delivery_department;
        $this->children = $obj->children;
        $this->numsections = $this->module_length != null ? $this->module_length : 1;
        $this->maxbytes = '67108864';

        // Set some required vars
        $this->shortname = $this->module_code;
        $this->fullname = $this->module_title;
        $this->visible = 0;
        
        // Force 2012/2013 on shortnames and titles for everything.
        $prev_year = date('Y', strtotime('1-1-' . $this->session_code . ' -1 year'));
        if (preg_match('/\(\d+\/\d+\)/is', $this->shortname) === 0) {
            $this->shortname .= " ($prev_year/$this->session_code)";
        }
        if (preg_match('/\(\d+\/\d+\)/is', $this->fullname) === 0) {
            $this->fullname .= " ($prev_year/$this->session_code)";
        }
    }

    /**
     * Update this course
     */
    public function update() {
        global $CONNECTDB;

        $sql = "UPDATE courses SET
                    moodle_id=?,
                    module_code=?,
                    module_title=?,
                    synopsis=?,
                    category_id=?,
                    state=?
                WHERE chksum=?";

        return $CONNECTDB->execute($sql, array(
            $this->moodle_id,
            $this->module_code,
            $this->module_title,
            $this->synopsis,
            $this->category,
            $this->state,
            $this->chksum
        ));
    }

    /**
     * Is this course unique?
     */
    public function is_unique() {
        global $CONNECTDB;

        $sql = "SELECT COUNT(*) as count FROM courses
                  WHERE session_code=?
                    AND module_code=?
                    AND chksum!=?
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
        return !empty($this->moodle_id);
    }

    /**
     * Create this course in Moodle
     *
     * @todo - Fire event off for an enrolment observer
     */
    public function create_moodle() {
        global $DB;

        // Give ourselves a category.
        if (!isset($this->category)) {
            print "No category for $this->chksum\n";
            return false;
        }

        // Create the course.
        try {
            $course = create_course($this);
            if (!$course) {
                return false;
            }
        } catch (\moodle_exception $e) {
            $msg = $e->getMessage();
            print "Error processing $this->chksum: $msg\n";
            return false;
        }

        // Update connect's reference.
        $this->moodle_id = $course->id;
        $this->state = 8;

        // Tell Connect about the new course.
        $this->update();

        // Add in sections.
        $DB->set_field('course_sections', 'name', $this->module_title, array (
            'course' => $course->id,
            'section' => 0
        ));

        // Add module extra details to the connect_course_dets table.
        $this->create_connect_extras();

        // Add the reading list module to our course if it is based in Canterbury.
        if ($this->campus_desc === 'Canterbury') {
            $this->create_reading_list();
        }

        // Add a news forum to the course.
        $this->create_forum();
    }

    /**
     * Returns connect_course_dets data.
     */
    private function get_dets_data() {
        global $CFG, $DB;

        // Try to find an existing set of data.
        $connect_data = $DB->get_record('connect_course_dets', array(
            'course' => $this->moodle_id
        ));

        // Create a data container.
        if (!$connect_data) {
            $connect_data = new \stdClass();
        }

        // Update the container's data.
        $connect_data->course = $this->moodle_id;
        $connect_data->campus = isset($this->campus_desc) ? $this->campus_desc : '';
        $connect_data->startdate = isset($this->startdate) ? $this->startdate : $CFG->default_course_start_date;
        $connect_data->enddate = isset($this->module_length) ? strtotime('+'. $this->module_length .' weeks', $connect_data->startdate) : $CFG->default_course_end_date;
        $connect_data->weeks = isset($this->module_length) ? $this->module_length : 0;

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
     * Add reading list module to this course
     */
    private function create_reading_list() {
        global $DB;

        $module = $DB->get_record('modules', array('name' => 'aspirelists'));

        // Create a data container.
        $rl = new \stdClass();
        $rl->course     = $this->moodle_id;
        $rl->name       = 'Reading list';
        $rl->intro      = '';
        $rl->introformat  = 1;
        $rl->category     = 'all';
        $rl->timemodified = time();

        // Create the instance.
        $instance = aspirelists_add_instance($rl, new \stdClass());

        // Find the first course section.
        $section = $DB->get_record_sql("SELECT id, sequence FROM {course_sections} WHERE course=:cid AND section=0", array (
            "cid" => $this->moodle_id
        ));

        // Create a module container.
        $cm = new \stdClass();
        $cm->course     = $this->moodle_id;
        $cm->module     = $module->id;
        $cm->instance   = $instance;
        $cm->section    = $section->id;
        $cm->visible    = 1;

        // Create the module.
        $coursemodule = add_course_module($cm);

        // Add it to the section.
        $DB->set_field('course_sections', 'sequence', "$coursemodule,$section->sequence", array (
            'id' => $section->id
        ));
    }

    /**
     * Add a forum module to this course
     */
    private function create_forum() {
        forum_get_course_forum($this->moodle_id, 'news');
    }

    /**
     * Update this course in Moodle
     * @todo
     */
    public function update_moodle() {
        global $DB;

        $course = $DB->get_record('course', array(
            'id' => $this->moodle_id
        ));

        $connect_data = $DB->get_record('connect_course_dets', array(
            'course' => $this->moodle_id
        ));

        // Update connect_course_dets.
        $this->create_connect_extras();

        // Update this course in Moodle.
        $this->visible = $course->visible;
        $uc = (object)array_merge((array)$course, (array)$this);
        update_course($uc);
    }

    /**
     * To String override
     */
    public function __toString() {
        return is_string($this->module_title) ? $this->module_title : "$this->chksum";
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

        return $data;
    }
}