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
class connect_testcase extends \advanced_testcase
{
	/**
	 * Clean up before/after a test
	 */
	protected function connect_cleanup() {
		global $CONNECTDB, $SHAREDB;

		$CONNECTDB->execute("TRUNCATE TABLE {group_enrollments}");
		$CONNECTDB->execute("TRUNCATE TABLE {enrollments}");
		$CONNECTDB->execute("TRUNCATE TABLE {courses}");
		$CONNECTDB->execute("TRUNCATE TABLE {groups}");
		$SHAREDB->execute("TRUNCATE TABLE {course_list}");
	}

	/**
	 * Insert a record into the Connect DB
	 */
	private function insertDB($table, $data) {
		global $CONNECTDB;

		$fields = implode(',', array_keys($data));
		$qms    = array_fill(0, count($data), '?');
		$qms    = implode(',', $qms);

		$CONNECTDB->execute("INSERT INTO {$table} ($fields) VALUES($qms)", $data);
	}

	/**
	 * Returns a valid enrolment for testing.
	 */
	protected function generate_enrolment($module_delivery_key, $role = 'student') {
		global $CFG;

		static $uid = 10000000;

		$generator = \advanced_testcase::getDataGenerator();
		$user = $generator->create_user();

		$data = array(
			"ukc" => $uid,
			"login" => $user->username,
			"title" => "Mx",
			"initials" => $user->firstname,
			"family_name" => $user->lastname,
			"session_code" => $CFG->connect->session_code,
			"module_delivery_key" => $module_delivery_key,
			"role" => $role,
			"chksum" => uniqid($uid),
			"id_chksum" => uniqid($uid),
			"state" => 1
		);

		$this->insertDB('enrollments', $data);

		$uid++;

		return $data;
	}

	/**
	 * Creates a bunch of enrolments.
	 */
	protected function generate_enrolments($count, $module_delivery_key, $role = 'student') {
		for ($i = 0; $i < $count; $i++) {
			$this->generate_enrolment($module_delivery_key, $role);
		}
	}

	/**
	 * Returns a valid group for testing.
	 */
	protected function generate_group($module_delivery_key) {
		global $CFG;

		static $uid = 100000;

		$data = array(
			"group_id" => $uid,
			"group_desc" => "Test Group: $uid",
			"session_code" => $CFG->connect->session_code,
			"module_delivery_key" => $module_delivery_key,
			"chksum" => uniqid($uid),
			"id_chksum" => uniqid($uid),
			"state" => 1
		);

		$this->insertDB('groups', $data);

		$uid++;

		return $data;
	}

	/**
	 * Creates a bunch of enrolments.
	 */
	protected function generate_groups($count, $module_delivery_key) {
		for ($i = 0; $i < $count; $i++) {
			$this->generate_group($module_delivery_key);
		}
	}

	/**
	 * Returns a valid group enrolment for testing.
	 */
	protected function generate_group_enrolment($group, $role = 'student') {
		global $CFG;

		static $uid = 10000000;

		$enrolment = $this->generate_enrolment($group['module_delivery_key'], $role);

		$data = array(
			"group_id" => $group['group_id'],
			"group_desc" => $group['group_desc'],
			"session_code" => $CFG->connect->session_code,
			"module_delivery_key" => $group['module_delivery_key'],
			"chksum" => uniqid($uid),
			"id_chksum" => uniqid($uid),
			"ukc" => $enrolment['ukc'],
			"login" => $enrolment['login'],
			"state" => 1
		);

		$this->insertDB('group_enrollments', $data);

		$uid++;

		return $data;
	}

	/**
	 * Creates a bunch of group enrolments.
	 */
	protected function generate_group_enrolments($count, $group, $role = 'student') {
		for ($i = 0; $i < $count; $i++) {
			$this->generate_group_enrolment($group, $role);
		}
	}

	/**
	 * Generates a random module code.
	 */
	private function generate_module_name() {
		static $prefix = array("Introduction to", "Advanced", "");
		static $subjects = array("Computing", "Science", "Arts", "Physics", "Film", "Theatre", "Engineering", "Electronics", "Media", "Philosophy");
		shuffle($prefix);
		shuffle($subjects);
		return $prefix[0] . " " . $subjects[1];
	}

	/**
	 * Generates a random module code.
	 */
	private function generate_module_code() {
		static $alphabet = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
		static $numbers = array("0","1","2","3","4","5","6","7","8","9");
		shuffle($alphabet);
		shuffle($numbers);
		return $alphabet[0] . $alphabet[1] . $numbers[0] . $numbers[1] . $numbers[2];
	}

	/**
	 * Returns a valid course module key for testing against.
	 */
	protected function generate_course() {
		global $CFG;

		static $delivery_key = 10000;

		$data = array(
			"module_delivery_key" => $delivery_key,
			"session_code" => $CFG->connect->session_code,
			"delivery_department" => '01',
			"campus" => 1,
			"module_version" => 1,
			"campus_desc" => 'Canterbury',
			"module_week_beginning" => 1,
			"module_length" => 12,
			"module_title" => $this->generate_module_name(),
			"module_code" => $this->generate_module_code(),
			"synopsis" => 'A test course',
			"chksum" => uniqid($delivery_key),
			"id_chksum" => uniqid($delivery_key),
			"category_id" => 1,
			"state" => 1
		);

		$this->insertDB('courses', $data);

		$delivery_key++;

		return $data;
	}

	/**
	 * Quick way of grabbing a valid module delivery key for
	 * a course that exists in Moodle.
	 */
	protected function generate_module_delivery_key() {
		global $CFG;

		// Generate a course.
		$course = $this->generate_course();
		$module_delivery_key = $course['module_delivery_key'];

		// Create in Moodle.
		$course = \local_connect\course::get_course_by_uid($module_delivery_key, $CFG->connect->session_code);
		$course->create_in_moodle();

		return $module_delivery_key;
	}
}
