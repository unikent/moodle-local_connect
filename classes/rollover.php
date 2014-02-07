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
 * Connect rollover container
 */
class rollover {

    /**
     * Returns a list of sources for rollover
     * 
     * @return array
     */
    public static function get_course_list($dist = '', $shortname = '') {
        global $CFG, $SHAREDB;

        if (!utils::enable_sharedb()) {
            return array();
        }

        $sql = 'SELECT * FROM {course_list} WHERE moodle_env = :current_env';
        $sql .= empty($dist) ? ' AND moodle_dist != :current_dist' : ' AND moodle_dist = :current_dist';

        $params = array(
            'current_env' => $CFG->kent->environment,
            'current_dist' => empty($dist) ? $CFG->kent->distribution : $dist
        );

        if (!empty($shortname)) {
            $shortname = "%" . $shortname . "%";
            $sql .= ' AND ' . $SHAREDB->sql_like('shortname', ':shortname', false);
            $params['shortname'] = $shortname;
        }

        return $SHAREDB->get_records_sql($sql, $params);
    }

    /**
     * Returns a list of sources for rollover
     * 
     * @return array
     */
    public static function get_source_list() {
        return static::get_course_list();
    }

    /**
     * Returns a list of targets for rollover
     * 
     * @return array
     */
    public static function get_target_list() {
        global $CFG;
        return static::get_course_list($CFG->kent->distribution);
    }

    /**
     * Populate the Shared DB list of courses.
     */
    public static function populate_sharedb() {
        global $CFG, $DB, $SHAREDB;

        if (!utils::enable_sharedb()) {
            return null;
        }

        // Grab a list of courses in Moodle.
        $courses = $DB->get_records('course', null, '', 'id,shortname,fullname,summary');

        // Grab a list of courses in ShareDB.
        $shared_courses = $SHAREDB->get_records('course_list', array(
            "moodle_env" => $CFG->kent->environment,
            "moodle_dist" => $CFG->kent->distribution
        ), '', 'moodle_id,id,shortname,fullname,summary');

        // Cross-reference and update.

        // First, all the new modules.
        foreach ($courses as $item) {
            // If this is already here, dont insert.
            if (isset($shared_courses[$item->id])) {
                continue;
            }

            // Insert.
            $SHAREDB->insert_record("course_list", array(
                "moodle_env" => $CFG->kent->environment,
                "moodle_dist" => $CFG->kent->distribution,
                "moodle_id" => $item->id,
                "shortname" => $item->shortname,
                "fullname" => $item->fullname,
                "summary" => $item->summary
            ));

            echo "Inserted {$item->id}.\n";
        }

        // Now, all the deleted modules.
        $ids = array_map(function($item) {
            return $item->id;
        }, $courses);
        $ids_to_stay = implode(', ', $ids);
        $SHAREDB->delete_records_select("course_list", "moodle_env=:env AND moodle_dist=:dist AND moodle_id NOT IN ($ids_to_stay)", array(
            "env" => $CFG->kent->environment,
            "dist" => $CFG->kent->distribution
        ));

        // Now update everything remaining.
        foreach ($courses as $item) {
            if (!isset($shared_courses[$item->id])) {
                continue;
            }

            $shared_obj = $shared_courses[$item->id];
            if ($shared_obj->shortname != $item->shortname || $shared_obj->fullname != $item->fullname || $shared_obj->summary != $item->summary) {
                // It needs updating.
                $shared_obj->shortname = $item->shortname;
                $shared_obj->fullname = $item->fullname;
                $shared_obj->summary = $item->summary;

                $SHAREDB->update_record('course_list', $shared_obj);

                echo "Updated {$item->id}.\n";
            }
        }
    }
}