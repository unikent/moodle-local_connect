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
}