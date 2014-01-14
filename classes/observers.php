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
 * Connect observers
 */
class observers {

    /**
     * Triggered when 'course_created' event is triggered.
     *
     * Adds the course to the SHAREDB clone-table
     *
     * @param \core\event\course_created $event
     */
    public static function course_created(\core\event\course_created $event) {
        global $CFG, $DB, $SHAREDB;

        if (!\local_connect\utils::is_enabled() || !\local_connect\utils::enable_new_features()) {
            return true;
        }
        
        // Update course listings DB
        $record = $DB->get_record('course', array(
            "id" => $event->objectid
        ));

        if ($record->id == 1) {
            return true;
        }

        $record->moodle_id = $record->id;
        $record->moodle_env = $CFG->kent->environment;
        $record->moodle_dist = $CFG->kent->distribution;
        $SHAREDB->insert_record("course_list", $record);

        // Sync Enrollments
        $connect_course = \local_connect\course::get_course($record->moodle_id);
        if ($connect_course !== false) {
            $connect_course->sync_enrolments();
        }

        return true;
    }
    
}