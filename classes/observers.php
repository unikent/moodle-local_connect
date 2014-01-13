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
 * Connect observers
 *
 * @package    core_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observers {

    /**
     * Triggered when 'course_created' event is triggered.
     *
     * @param \core\event\course_created $event
     */
    public static function course_created(\core\event\course_created $event) {
        global $CFG, $DB, $SHAREDB;
        
        // Update course listings DB
        if (\local_connect\utils::is_enabled()) {
            $record = $DB->get_record('course', array(
                "id" => $event->objectid
            ));

            if ($record->id == 1) {
                continue;
            }

            $record->moodle_id = $record->id;
            $record->moodle_env = $CFG->kent->environment;
            $record->moodle_dist = $CFG->kent->distribution;
            $SHAREDB->insert_record("course_list", $record);
        }

        return true;
    }

    /**
     * A user enrollment has occurred.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        // TODO
        return true;
    }
}