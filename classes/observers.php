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
     * Triggered when 'course_created' event is triggered.
     *
     * Adds the course to the SHAREDB clone-table
     *
     * @param \core\event\course_created $event
     * @return unknown
     */
    public static function course_created(\core\event\course_created $event) {
        global $CFG, $DB, $SHAREDB;

        // Update ShareDB if it is enabled.
        if (utils::enable_sharedb()) {
            // Update course listings DB.
            $record = $DB->get_record('course', array(
                "id" => $event->objectid
            ));

            if ($record->id == 1) {
                return true;
            }

            $record->moodle_id = $record->id;
            $record->moodle_env = $CFG->kent->environment;
            $record->moodle_dist = $CFG->kent->distribution;

            $conditions = array(
                "moodle_id" => $record->id,
                "moodle_env" => $record->moodle_env,
                "moodle_dist" => $record->moodle_dist
            );

            // Is there one of these already?
            if ($SHAREDB->record_exists("course_list", $conditions)) {
                return true;
            }

            unset($record->id);

            $SHAREDB->insert_record("course_list", $record);
        }

        return true;
    }

    /**
     * Triggered when 'course_updated' event is triggered.
     *
     * Updates the course in the SHAREDB clone-table
     *
     * @param \core\event\course_updated $event
     * @return unknown
     */
    public static function course_updated(\core\event\course_updated $event) {
        global $CFG, $DB, $SHAREDB;

        if ($event->objectid == 1) {
            return true;
        }

        // Update ShareDB if it is enabled.
        if (utils::enable_sharedb()) {
            // Update course listings DB.
            $moodle = $DB->get_record('course', array(
                "id" => $event->objectid
            ));

            $record = $SHAREDB->get_record('course_list', array(
                "moodle_id" => $moodle->id,
                "moodle_env" => $CFG->kent->environment,
                "moodle_dist" => $CFG->kent->distribution
            ));

            $record->shortname = $moodle->shortname;
            $record->fullname = $moodle->fullname;
            $record->summary = $moodle->summary;

            $SHAREDB->update_record("course_list", $record);
        }

        return true;
    }

    /**
     * Triggered when 'course_deleted' event is triggered.
     *
     * Deletes the course from the SHAREDB clone-table
     *
     * @param \core\event\course_deleted $event
     * @return unknown
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $CFG, $DB, $SHAREDB;

        if ($event->objectid == 1) {
            return true;
        }

        // Update any mids.
        $DB->set_field('connect_course', 'mid', null, array(
            'mid' => $event->objectid
        ));

        // Update ShareDB if it is enabled.
        if (utils::enable_sharedb()) {
            // Update course listings DB.
            $moodle = $DB->get_record('course', array(
                "id" => $event->objectid
            ));

            $SHAREDB->delete_records("course_list", array(
                "moodle_id" => $moodle->id,
                "moodle_env" => $CFG->kent->environment,
                "moodle_dist" => $CFG->kent->distribution
            ));
        }

        return true;
    }


    /**
     * Triggered when 'user_created' event is triggered.
     *
     * @param \core\event\user_created $event
     * @return unknown
     */
    public static function user_created(\core\event\user_created $event) {
        global $DB, $USER;

        // Grab user info.
        $username = $DB->get_field('user', 'username', array(
            "id" => $event->objectid
        ));

        $obj = $DB->get_record('connect_user', array(
            "login" => $username
        ));

        // If there is no valid connect user, bail out.
        if (!$obj) {
            return true;
        }

        // Update any mids.
        $obj->mid = $event->objectid;
        $DB->update_record('connect_user', $obj);

        // Grab connect object.
        $obj = user::get($obj->id);

        // If we created the user on first login, sync enrolments.
        // TODO - make this a "task" in 2.7.
        if ($USER->id === $obj->mid) {
            // Sync Enrollments.
            $enrolments = enrolment::get_for_user($obj);
            foreach ($enrolments as $enrolment) {
                $enrolment->create_in_moodle();
            }

            // Sync Group Enrollments.
            $enrolments = group_enrolment::get_for_user($obj);
            foreach ($enrolments as $enrolment) {
                $enrolment->create_in_moodle();
            }
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
        $DB->set_field('connect_user', 'mid', null, array(
            'mid' => $event->objectid
        ));

        return true;
    }


    /**
     * Triggered when 'group_created' event is triggered.
     *
     * @param \core\event\group_created $event
     * @return unknown
     */
    public static function group_created(\core\event\group_created $event) {
        global $DB;

        if (!utils::enable_new_features()) {
            return true;
        }

        $group = $event->get_record_snapshot('groups', $event->objectid);

        $courses = $DB->get_records('connect_course', array(
            'mid' => $group->courseid
        ));

        foreach ($courses as $course) {
            $groups = $DB->get_records('connect_group', array(
                'courseid' => $course->id,
                'name' => $group->name
            ));

            foreach ($groups as $group) {
                // Reset mid.
                if ($group->id) {
                    $obj = group::get($group->id);
                    if ($obj->mid !== $event->objectid) {
                        $obj->mid = $event->objectid;
                        $obj->save();
                    }
                }
            }
        }

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
        $DB->set_field('connect_group', 'mid', null, array(
            'mid' => $event->objectid
        ));

        return true;
    }

}
