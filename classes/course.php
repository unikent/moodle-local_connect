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

    /** Our session code */
    private $session_code;

    /** Our module code */
    private $module_code;

    /** Our module title */
    private $module_title;

    /** Our category id */
    private $category_id;

    /** Our synopsis */
    private $synopsis;

    /** Our state */
    private $state;


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
     */
    public static function get_courses() {
        // Set up our various variables
        $cache = cache::make('local_connect', 'kent_connect');
        $pdo = connect_db();
        $data = array();

        // Cache in MUC
        $cache_content = $cache->get('lcproxy_getCourses');
        if ($cache_content !== false) {
            return $cache_content;
        }

        // Blame Patrick for this, ignore what the git blame says. - Sky
        $query = <<<SQL
          SELECT 
            c1.chksum,
            CONCAT('[',COALESCE(GROUP_CONCAT(CONCAT('"',statecode.state,'"')),''),']') state,
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
            CONCAT('[',COALESCE(GROUP_CONCAT(CONCAT('"',c2.chksum,'"')),''),']') children
          FROM
            courses c1
              LEFT OUTER JOIN
            courses c2 ON c1.module_delivery_key = c2.parent_id
          LEFT OUTER JOIN (SELECT 'unprocessed' state, 1 code
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
          SELECT 'disengaged_from_moodle' state, 64 code) statecode
          ON (c1.state & statecode.code) > 0
    SQL;
        // End Patrick blame (unless he has left, then it's all his fault)

        // Add the category restrictions if there are any
        if (isset($_GET['category_restrictions'])) {
          $inQuery = implode(',', array_fill(0, count($_GET['category_restrictions']), '?'));
          $query .= " WHERE c1.category_id IN ({$inQuery})";
        }

        $query .= ' GROUP BY c1.chksum';

        $q = $pdo->prepare($query);

        if (isset($_GET['category_restrictions'])) {
          foreach ($_GET['category_restrictions'] as $k => $id) {
            $q->bindValue(($k+1), $id);
          }
        }
        // Done!

        $q->execute();

        $result = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $obj) {
          if (!empty($obj['children'])) {
            $obj['children'] = json_decode($obj['children']);
          }
          if (!empty($obj['state'])) {
            $obj['state'] = json_decode($obj['state']);
          }
          $data[] = $obj;
        }

        $cache->set('lcproxy_getCourses', $data);

        return $data;
    }
}