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
class observers
{
    /**
     * Triggered when 'course_updated' event is triggered.
     *
     * @param \core\event\course_updated $event
     * @return unknown
     */
    public static function course_updated(\core\event\course_updated $event) {
        global $DB, $USER;

        if (!$USER || $USER->id <= 2) {
            return true;
        }

        // Set new lock status.
        $DB->execute("REPLACE INTO {connect_course_locks} (mid, locked) VALUES (:courseid, 0)", array(
            "courseid" => $event->objectid
        ));

        return true;
    }

    /**
     * Triggered when 'course_deleted' event is triggered.
     *
     * @param \core\event\course_deleted $event
     * @return unknown
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;

        // Update any mids.
        $DB->set_field('connect_course', 'mid', 0, array(
            'mid' => $event->objectid
        ));

        return true;
    }


    /**
     * Triggered when 'user_created' event is triggered.
     *
     * @param \core\event\user_created $event
     * @return unknown
     */
    public static function user_created(\core\event\user_created $event) {
        global $DB;

        // Grab user info.
        $username = $DB->get_field('user', 'username', array(
            "id" => $event->objectid
        ));

        $user = $DB->get_record('connect_user', array(
            "login" => $username
        ));

        // If there is no valid connect user, bail out.
        if (!$user) {
            return true;
        }

        // Update any mids.
        $user->mid = $event->objectid;
        $DB->update_record('connect_user', $user);

        // Grab connect object.
        $user = user::from_sql_result($user);

        // If we created the user on first login, sync enrolments.
        // TODO - make this a "task" in 2.7.

        // Sync Enrollments.
        $enrolments = enrolment::get_by("userid", $user->id, true);
        foreach ($enrolments as $enrolment) {
            $enrolment->create_in_moodle();
        }

        // Sync Group Enrollments.
        $enrolments = group_enrolment::get_by("userid", $user->id, true);
        foreach ($enrolments as $enrolment) {
            $enrolment->create_in_moodle();
        }

        return true;
    }


    /**
     * Triggered when 'user_deleted' event is triggered.
     *
     * @param \core\event\user_deleted $event
     * @return unknown
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;

        // Update any mids.
        $DB->set_field('connect_user', 'mid', 0, array(
            'mid' => $event->objectid
        ));

        return true;
    }

    /**
     * Triggered when 'group_deleted' event is triggered.
     *
     * @param \core\event\group_deleted $event
     * @return unknown
     */
    public static function group_deleted(\core\event\group_deleted $event) {
        global $DB;

        // Update any mids.
        $DB->set_field('connect_group', 'mid', 0, array(
            'mid' => $event->objectid
        ));

        return true;
    }

}
