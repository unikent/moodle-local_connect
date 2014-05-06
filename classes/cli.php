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
 * Connect CLI helpers
 */
class cli
{
    /**
     * Map status to actions.
     */
    public static function map_status($status, $obj) {
        switch ($status) {
            case data::STATUS_CREATE:
                mtrace("  Created: " . $obj->id);
            break;
            case data::STATUS_MODIFY:
                mtrace("  Modified: " . $obj->id);
            break;
            case data::STATUS_DELETE:
                mtrace("  Deleted: " . $obj->id);
            break;
            default:
            break;
        }
    }

    /**
     * Helper to fix up mids.
     * Shouldnt be needed (observers) but is here "just in case".
     */
    public static function fix_mids() {
        global $DB;

        // Fix courses.
        $sql = <<<SQL
            SELECT cc.id, cc.mid
            FROM {connect_course} cc
            LEFT OUTER JOIN {course} c ON c.id=cc.mid
            WHERE cc.mid > 0 AND c.id IS NULL
SQL;
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            $record->mid = null;
            $DB->update_record('connect_course', $record);
        }
    }

    /**
     * Run the course sync cron
     */
    public static function course_sync($dry = false, $mid = null) {
        $conditions = array();

        if (isset($mid)) {
            $conditions['mid'] = $mid;
            mtrace("Synchronizing course: '{$mid}'...");
        } else {
            mtrace("Synchronizing courses...");
        }

        // Just run a batch_all on the set.
        course::batch_all(function ($obj) use($dry) {
            try {
                $result = $obj->sync($dry);
                cli::map_status($result, $obj);
            } catch (Excepton $e) {
                $msg = $e->getMessage();
                mtrace("  Error: {$msg}");
            }
        }, $conditions);

        mtrace("done!");

        return true;
    }

    /**
     * Run the enrolment sync cron
     */
    public static function enrolment_sync($dry = false, $mid = null) {
        // If we dont have an mid, this is easy.
        if (!isset($mid)) {
            mtrace("Synchronizing enrolments...");

            // Just run a batch_all on the set.
            enrolment::batch_all(function ($obj) use($dry) {
                $result = $obj->sync($dry);
                cli::map_status($result, $obj);
            });

            mtrace("  done.");

            return true;
        }

        mtrace("Synchronizing enrolments for course: '{$mid}'...");

        // Get the connect version of the course.
        $courses = course::get_by_moodle_id($mid);

        // Validate the course.
        if (empty($courses)) {
            mtrace("Course does not exist in Moodle: {$mid}");
            return false;
        }

        // We have a valid course(s)!
        foreach ($courses as $course) {
            if ($course->is_in_moodle()) {
                $course->sync_enrolments();
            }
        }

        mtrace("  done.");

        return true;
    }

    /**
     * Run the group sync cron
     */
    public static function group_sync($dry = false, $mid = null) {
        // If we dont have a moodle id limiting us, batch it all.
        if (!isset($mid)) {
            mtrace("Synchronizing groups...");

            // Just run a batch_all on the set.
            group::batch_all(function ($obj) use($dry) {
                $result = $obj->sync($dry);
                cli::map_status($result, $obj);
            });

            mtrace("  done.");

            return true;
        }

        mtrace("Synchronizing groups for course: '{$mid}'...");

        // Get the connect version of the course.
        $courses = course::get_by_moodle_id($mid);

        // Validate the course.
        if (empty($courses)) {
            mtrace("Course does not exist in Moodle: {$mid}");
            return false;
        }

        // We have a valid course(s)!
        foreach ($courses as $course) {
            if ($course->is_in_moodle()) {
                $course->sync_groups();
            }
        }

        mtrace("  done!");
    }

    /**
     * Run the group enrolment sync cron
     */
    public static function group_enrolment_sync($dry = false, $mid = null) {
        // If we dont have a moodle id limiting us, batch it all.
        if (!isset($mid)) {
            mtrace("Synchronizing group enrolments...");

            // Just run a batch_all on the set.
            group_enrolment::batch_all(function ($obj) use($dry) {
                $result = $obj->sync($dry);
                cli::map_status($result, $obj);
            });

            mtrace("  done.");

            return true;
        }

        mtrace("Synchronizing group enrolments for course: '{$mid}'...");

        // Get the connect version of the course.
        $courses = course::get_by_moodle_id($mid);

        // Validate the course.
        if (empty($courses)) {
            mtrace("Course does not exist in Moodle: {$mid}");
            return false;
        }

        // We have a valid course!
        foreach ($courses as $course) {
            if ($course->is_in_moodle()) {
                $course->sync_group_enrolments();
            }
        }

        mtrace("  done!");
    }

}
