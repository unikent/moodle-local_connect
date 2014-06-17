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

namespace local_connect\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for Connect Tests
 */
abstract class connect_testcase extends \advanced_testcase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        global $CFG, $DB;

        $CFG->local_connect_enable = true;
        $CFG->local_connect_enable_new_features = true;
        $CFG->local_connect_enable_observers = true;
        $CFG->local_connect_enable_cron = true;
        $CFG->local_connect_enable_rollover = true;

        // Create new campus records.
        $DB->insert_record("connect_campus", array("name" => "Canterbury"));
        $DB->insert_record("connect_campus", array("name" => "Medway"));

        // Create new role records.
        $DB->insert_record("connect_role", array("mid" => 0, "name" => "student"));
        $DB->insert_record("connect_role", array("mid" => 0, "name" => "teacher"));
        $DB->insert_record("connect_role", array("mid" => 0, "name" => "convenor"));
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        global $CFG, $DB, $SHAREDB;

        unset($CFG->local_connect_enable);
        unset($CFG->local_connect_enable_new_features);
        unset($CFG->local_connect_enable_observers);
        unset($CFG->local_connect_enable_cron);
        unset($CFG->local_connect_enable_rollover);

        $SHAREDB->execute("TRUNCATE TABLE {shared_courses}");

        // Delete the roles too.
        $DB->delete_records('role', array('shortname' => 'sds_student'));
        $DB->delete_records('role', array('shortname' => 'sds_teacher'));
        $DB->delete_records('role', array('shortname' => 'sds_convenor'));
        $DB->delete_records('role', array('shortname' => 'convenor'));
    }

    /**
     * Returns a valid user for testing.
     */
    protected function generate_user() {
        global $DB;

        // Create at the moodle-level.
        $generator = \advanced_testcase::getDataGenerator();
        $user = $generator->create_user();

        static $uid = 10000000;

        return $DB->insert_record("connect_user", array(
            "mid" => $user->id,
            "ukc" => $uid++,
            "login" => $user->username,
            "title" => "Mx",
            "initials" => $user->firstname,
            "family_name" => $user->lastname
        ));
    }

    /**
     * Returns a valid enrolment for testing.
     */
    protected function generate_enrolment($id, $role = 1) {
        global $DB;

        if (is_string($role)) {
            $role = $DB->get_field("connect_role", "id", array("name" => $role));
        }

        return $DB->insert_record('connect_enrolments', array(
            "mid" => 0,
            "courseid" => $id,
            "userid" => $this->generate_user(),
            "roleid" => $role
        ));
    }

    /**
     * Creates a bunch of enrolments.
     */
    protected function generate_enrolments($count, $id, $role = 1) {
        global $DB;

        if (is_string($role)) {
            $role = (int)$DB->get_field("connect_role", "id", array("name" => $role));
        }

        for ($i = 0; $i < $count; $i++) {
            $this->generate_enrolment($id, $role);
        }
    }

    /**
     * Returns a valid group for testing.
     */
    protected function generate_group($courseid) {
        global $DB;
        static $uid = 100000;
        return $DB->insert_record('connect_group', array(
            "mid" => 0,
            "courseid" => $courseid,
            "name" => "Test Group: " . $uid++
        ));
    }

    /**
     * Creates a bunch of enrolments.
     */
    protected function generate_groups($count, $courseid) {
        for ($i = 0; $i < $count; $i++) {
            $this->generate_group($courseid);
        }
    }

    /**
     * Returns a valid group enrolment for testing.
     */
    protected function generate_group_enrolment($groupid, $role = 'student') {
        global $DB;

        $courseid = $DB->get_field('connect_group', 'courseid', array(
            "id" => $groupid
        ));

        $enrolmentid = $this->generate_enrolment($courseid, $role);

        $userid = $DB->get_field('connect_enrolments', 'userid', array(
            "id" => $enrolmentid
        ));

        return $DB->insert_record('connect_group_enrolments', array(
            "mid" => 0,
            "groupid" => $groupid,
            "userid" => $userid
        ));
    }

    /**
     * Creates a bunch of group enrolments.
     */
    protected function generate_group_enrolments($count, $group, $role = 'student') {
        global $DB;

        if (is_string($role)) {
            $role = (int)$DB->get_field("connect_role", "id", array("name" => $role));
        }

        for ($i = 0; $i < $count; $i++) {
            $this->generate_group_enrolment($group, $role);
        }
    }

    /**
     * Generates a random module code.
     */
    private function generate_module_name() {
        static $prefix = array("Introduction to", "Advanced", "");
        static $subjects = array(
            "Computing", "Science", "Arts", "Physics", "Film", "Theatre", "Engineering", "Electronics", "Media", "Philosophy"
        );
        shuffle($prefix);
        shuffle($subjects);
        return $prefix[0] . " " . $subjects[1];
    }

    /**
     * Generates a random module code.
     */
    private function generate_module_code() {
        static $alphabet = array(
            "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M",
            "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"
        );
        static $numbers = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
        shuffle($alphabet);
        shuffle($numbers);
        return $alphabet[0] . $alphabet[1] . $numbers[0] . $numbers[1] . $numbers[2];
    }

    /**
     * Returns a valid course module key for testing against.
     * @todo Extend to support extra campuses and categories.
     */
    protected function generate_course() {
        global $CFG, $DB;

        static $key = 10000;

        return $DB->insert_record('connect_course', array(
            "mid" => 0,
            "module_delivery_key" => $key++,
            "session_code" => $CFG->connect->session_code,
            "module_version" => 1,
            "campusid" => 1,
            "module_week_beginning" => 1,
            "module_length" => 12,
            "week_beginning_date" => strftime('%Y-%m-%d %H:%M:%S'),
            "module_title" => $this->generate_module_name(),
            "module_code" => $this->generate_module_code(),
            "synopsis" => 'A test course',
            "category" => 1
        ));
    }

    /**
     * Returns a valid course module key for testing against.
     */
    protected function generate_courses($count) {
        for ($i = 0; $i < $count; $i++) {
            $this->generate_course();
        }
    }
}
