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
 * Connect courses container
 */
class course {

    /** Our UID */
    public $uid;

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

    /** Our module delivery key */
    public $module_delivery_key;

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

    public static $states = array(
        'unprocessed' => 1,
        'scheduled' => 2,
        'processing' => 4,
        'created_in_moodle' => 8,
        'failed_in_moodle' => 16,
        'disengage' => 32,
        'disengaged_from_moodle' => 64
    );

    /**
     * Constructor to build from a database object
     * @param unknown $obj
     */
    public function __construct($obj) {
        $this->chksum = $obj->chksum;
        $this->state = $obj->state;
        $this->module_code = $obj->module_code;
        $this->module_title = $obj->module_title;
        $this->module_version = $obj->module_version;
        $this->module_delivery_key = $obj->module_delivery_key;
        $this->campus = $obj->campus;
        $this->campus_desc = $obj->campus_desc;
        $this->synopsis = $obj->synopsis;
        $this->module_week_beginning = $obj->module_week_beginning;
        $this->module_length = $obj->module_length;
        $this->sink_deleted = $obj->sink_deleted;
        $this->student_count = $obj->student_count;
        $this->teacher_count = $obj->teacher_count;
        $this->convenor_count = $obj->convenor_count;
        $this->parent_id = $obj->parent_id;
        $this->session_code = $obj->session_code;
        $this->category = $obj->category_id;
        $this->delivery_department = $obj->delivery_department;
        $this->children = isset($obj->children) ? $obj->children : null;
        $this->numsections = $this->module_length != null ? $this->module_length : 1;
        $this->link = isset($obj->link) ? $obj->link : 0;
        $this->maxbytes = '67108864';

        // Get our UID
        $this->uid = $obj->module_delivery_key . "-" . $obj->session_code;

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

        // Grab our Moodle ID (if we have one).
        $this->set_moodle_id();
    }

    /**
     * Determines our Moodle ID
     */
    private function set_moodle_id() {
        global $DB;

        $data = $DB->get_record('connect_course_chksum', array(
            'module_delivery_key' => $this->module_delivery_key,
            'session_code' => $this->session_code
        ));

        if ($data) {
            $this->moodle_id = $data->courseid;
        }
    }


    /**
     * Update this course in Connect
     * @return unknown
     */
    public function update() {
        global $CONNECTDB;

        $sql = "UPDATE courses SET
                    parent_id=?,
                    moodle_id=?,
                    module_code=?,
                    module_title=?,
                    synopsis=?,
                    category_id=?,
                    state=?
                WHERE module_delivery_key=? AND session_code=?";

        return $CONNECTDB->execute($sql, array(
                $this->parent_id,
                $this->moodle_id,
                $this->module_code,
                $this->module_title,
                $this->synopsis,
                $this->category,
                $this->state,
                $this->module_delivery_key,
                $this->session_code
            ));
    }


    /**
     * Is this course unique?
     * @return unknown
     */
    public function is_unique() {
        global $CONNECTDB;

        $sql = "SELECT COUNT(*) as count FROM courses
                  WHERE session_code=?
                    AND module_code=?
                    AND chksum!=?
                    AND (state & ?) <> 0";

        $params = array(
            $this->session_code,
            $this->module_code,
            $this->chksum,
            self::$states['scheduled'] |
            self::$states['processing'] |
            self::$states['created_in_moodle']
        );

        return $CONNECTDB->count_records_sql($sql, $params) === 0;
    }


    /**
     * Has this course been scheduled for rollover?
     * @return unknown
     */
    public function is_scheduled() {
        return in_array($this->state, array(2, 4, 6, 8, 10, 12));
    }


    /**
     * Has this course been created in Moodle?
     * @return unknown
     */
    public function is_created() {
        return !empty($this->moodle_id);
    }


    /**
     * Does this course have a unique shortname?
     * @return unknown
     */
    public function has_unique_shortname() {
        global $CONNECTDB;
        $sql = "SELECT COUNT(*) as count FROM courses WHERE module_code=?";
        return $CONNECTDB->count_records_sql($sql, array($this->module_code)) == 1;
    }


    /**
     * Has this course changed at all?
     * @return unknown
     */
    public function has_changed() {
        global $DB;

        // Cant do this if the course doesnt exist.
        if (!$this->is_created()) {
            return false;
        }

        // Check our chksum against the value stored in the DB
        $chksum = $DB->get_record('connect_course_chksum', array (
            'courseid' => $this->moodle_id,
            'module_delivery_key' => $this->module_delivery_key,
            'session_code' => $this->session_code
        ));

        // If there is no chksum, we are dealing with a new course so add
        // a placeholder and return true.
        if (!$chksum) {
            $DB->insert_record_raw("connect_course_chksum", array(
                "courseid" => $this->moodle_id,
                "module_delivery_key" => $this->module_delivery_key,
                "session_code" => $this->session_code,
                "chksum" => 'updateme'
            ));
            return true;
        }

        return $chksum->chksum != $this->chksum;
    }

    /**
     * Create this course in Moodle
     * @param unknown $shortname_ext (optional)
     * @return unknown
     */
    public function create_in_moodle($shortname_ext = "") {
        global $DB;

        // Check we have a category.
        if (!isset($this->category)) {
            print "No category for $this->chksum\n";
            return false;
        }

        $this->shortname = $this->shortname . " " . $shortname_ext;

        // Does this shortname exist?
        $course = $DB->get_record('course', array('shortname' => $this->shortname));
        if ($course) {
            if ($this->link === 0) {
                // Yes! Link them together.
                $link_course = static::get_course($course->id);
                if ($link_course) {
                    $link_course->add_child($this);
                }
            }

            $this->moodle_id = $course->id;
            return false;
        }

        // Create the course.
        try {
            $course = create_course($this);
            if (!$course) {
                throw new \moodle_exception("Unknown");
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

        // Add to tracking table.
        $tracker = $DB->get_record('connect_course_chksum', array(
                'module_delivery_key' => $this->module_delivery_key,
                'session_code' => $this->session_code
            ));
        if ($tracker) {
            $DB->set_field('connect_course_chksum', 'chksum', $this->chksum, array (
                    'module_delivery_key' => $this->module_delivery_key,
                    'session_code' => $this->session_code
                ));
        } else {
            $DB->insert_record_raw("connect_course_chksum", array(
                    "courseid" => $this->moodle_id,
                    "module_delivery_key" => $this->module_delivery_key,
                    "session_code" => $this->session_code,
                    "chksum" => $this->chksum
                ));
        }

        // Sync our enrolments.
        $this->sync_enrolments();

        // Sync our groups.
        $this->sync_groups();

        return true;
    }

    /**
     * Create this course in Moodle
     * @param unknown $shortname_ext (optional)
     * @return unknown
     */
    public function create_moodle($shortname_ext = "") {
        // TODO - deprecate
        return $this->create_in_moodle($shortname_ext);
    }


    /**
     * Link a course to this course
     * @param unknown $target
     */
    private function create_link($target) {
        // Create a linked course.
        $data = clone($this);
        $data->module_delivery_key = $this->chksum;
        $data->primary_child = $this->chksum;
        $data->shortname = "$this->shortname/$target->shortname";
        $data->chksum = $data->id_chksum = uniqid("link-");
        $data->link = 1;
        $data = new course($data);

        // Create the course in Moodle.
        $data->create_moodle();

        // Add children.
        $data->add_child($this);
        $data->add_child($target);
    }


    /**
     * Link a course to this course
     * @param unknown $target
     * @return unknown
     */
    private function add_child($target) {
        global $CONNECTDB, $DB;

        print "Linking $this->moodle_id with $target->chksum.\n";

        // Is this a link course?
        if ($this->link === 0) {
            return $this->create_link($target);
        }

        // Link them up
        $CONNECTDB->set_field('courses', 'parent_id', $this->chksum, array (
                'chksum' => $target->chksum
            ));
        $CONNECTDB->set_field('courses', 'moodle_id', $this->moodle_id, array (
                'chksum' => $target->chksum
            ));
        $CONNECTDB->set_field('courses', 'state', '8', array (
                'chksum' => $target->chksum
            ));
    }


    /**
     * Returns connect_course_dets data.
     * @return unknown
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
        $connect_data->startdate = isset($this->startdate) ? $this->startdate : $CFG->kent->default_course_start_date;
        $connect_data->enddate = isset($this->module_length) ? strtotime('+'. $this->module_length .' weeks', $connect_data->startdate) : $CFG->kent->default_course_end_date;
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

        // Set some special vars.
        $this->visible = $course->visible;
        $uc = (object)array_merge((array)$course, (array)$this);
        $uc->shortname = $course->shortname;

        // Update this course in Moodle.
        update_course($uc);

        // Update chksum tracker.
        $DB->set_field('connect_course_chksum', 'chksum', $this->chksum, array (
            'module_delivery_key' => $this->module_delivery_key,
            'session_code' => $this->session_code
        ));
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
                $course->moodle_id = 0;
                $course->parent_id = 0;
                $course->update();
            }
        }

        // Step 1 - Move to the 'removed category'.

        $category = utils::get_removed_category();

        $course = $DB->get_record('course', array('id' => $this->moodle_id));

        $course->category = $category;
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
        $enrolments = enrolment::get_enrolments_for_course($this);
        foreach ($enrolments as $enrolment) {
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
        $groups = group::get_for_course($this);
        foreach ($groups as $group) {
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
     * Returns an array of all courses in Connect
     *
     * @param array category_restrictions A list of categories we dont want
     * @param boolean obj_form Should all objects be of this class type?
     * @param unknown $category_restrictions (optional)
     * @param unknown $obj_form (optional)
     * @return unknown
     */
    public static function get_courses($category_restrictions = array(), $obj_form = false) {
        global $CFG, $CONNECTDB;

        $sql = "SELECT
                    c1.chksum,
                    CONCAT('[',COALESCE(GROUP_CONCAT(CONCAT('\"',statecode.state,'\"')),''),']') state,
                    c1.module_code,
                    c1.module_title,
                    c1.module_version,
                    c1.campus,
                    c1.campus_desc,
                    c1.synopsis,
                    c1.module_delivery_key,
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
                        ON c1.module_delivery_key = c2.parent_id AND c1.session_code = c2.session_code
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
                        ON (c1.state & statecode.code) > 0
                  WHERE c1.session_code = :sesscode";

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

                if (!empty($obj->state)) {
                    $obj->state = json_decode($obj->state);
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
            if (!$connect_course->create_moodle($shortname_ext)) {
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
    public static function merge($input) {
        global $CONNECTDB;

        $link_course = array(
            'module_code' => $input->code,
            'module_title' => $input->title,
            'primary_child' => $input->primary_child,
            'synopsis' => $input->synopsis,
            'category_id' => $input->category,
            'state' => self::$states['scheduled'],
            'moodle_id' => null
        );

        // Lets make sure the module_code is unique.d
        if ($CONNECTDB->count_records('courses', array('module_code' => $link_course['module_code'])) > 0) {
            return array('error_code' => 'duplicate');
        }

        // Find all the courses we want to link to and make sure they exist.
        $courses = $CONNECTDB->get_records_list('courses', 'chksum', $input->link_courses);
        if (count($courses) != count($input->link_courses)) {
            return array('error_code' => 'invalid_course');
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
            $link = self::get_course_by_uid($link->module_delivery_key, $link->session_code);

            // Filter out children.
            $children = array_filter($courses, function($v) use ($link) {
                return ($v->chksum != $link->chksum) && (($v->state & course::$states['unprocessed']) > 0);
            });

            // Map them to objects.
            $children = array_map($courses, function($v) {
                return course::get_course_by_uid($v->module_delivery_key, $v->session_code);
            });

            // Create the links.
            foreach ($children as $child) {
                $link->add_child($child);
            }

            return array();
        }

        // How many are already created?
        $already_created = array_filter($courses, function($v) {
            return (($v->state & course::$states['created_in_moodle']) > 0) && $v->moodle_id;
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
            $f = array_shift($already_created);
            $link_course['moodle_id'] = $f->moodle_id;
            $link_course['state'] = self::$states['created_in_moodle'];
        }

        // Grab hold of the 'primary' delivery and base our details on that.

        $t = array_filter($courses, function($v) use ($link_course) {
            return $v->chksum == $link_course['primary_child'];
        });
        $primary_child = array_shift($t);

        $keys = array_keys($courses);
        $parent = $courses[array_pop($keys)];
        if ($primary_child !== null) {
            $parent = $primary_child;
        } else {
            $t = array_filter($courses, function($v) {
                return $v->campus_desc == 'Canterbury';
            });
            $only_canterbury = array_shift($t);

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

        // Create it and join them up.
        $tr = $CONNECTDB->start_delegated_transaction();
        $uuid = $CONNECTDB->get_record_sql('SELECT uuid() AS uuid');
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
        $CONNECTDB->execute($sql, array(
            $uuid->uuid,
            $uuid->uuid,
            $link_course['primary_child'],
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

        $CONNECTDB->execute('update courses set parent_id = ? where find_in_set(chksum, ?)', array(
            $uuid->uuid,
            join(',', $input->link_courses)
        ));

        $tr->allow_commit();

        // Find the new course.
        $link = self::get_course_by_uid($uuid->uuid);

        // Add children.
        foreach ($input->link_courses as $child) {
            $child = self::get_course_by_uid($child->module_delivery_key, $child->session_code);
            $link->add_child($child);
        }

        return array();
    }


    /**
     *
     * @param unknown $in_courses
     * @return unknown
     */
    public static function unlink($in_courses) {
        global $CONNECTDB, $STOMP;
        $r = array();

        foreach ($in_courses as $c) {
            $course = $CONNECTDB->get_record('courses', array('chksum' => $c));
            if ($course == null) {
                $r[] = array(
                    'error_code' => 'does_not_exist',
                    'id' => $c
                );
            } else if ($course->parent_id == null) {
                $r[] = array(
                    'error_code' => 'not_link_course',
                    'id' => $c
                );
            } else if (($course->state & self::$states['created_in_moodle']) == 0) {
                $r[] = array(
                    'error_code' => 'not_created',
                    'id' => $c
                );
            } else {
                $STOMP->send('connect.job.unlink_course', $course->chksum);
            }
        }

        return $r;
    }
}
