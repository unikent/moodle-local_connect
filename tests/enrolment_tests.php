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

defined('MOODLE_INTERNAL') || die();

/**
 * Tests new Kent enrolment code
 */
class kent_enrolment_tests extends local_connect\tests\connect_testcase
{
	/**
	 * Make sure we can grab a valid list of enrolments.
	 */
	public function test_enrolment_list() {
		global $CFG, $DB, $CONNECTDB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$module_delivery_key = $this->generate_course();
		$course = \local_connect\course::get_course_by_uid($module_delivery_key, $CFG->connect->session_code);
		$this->assertTrue($course->create_in_moodle());

		// Next insert a couple of enrolments on this course.
		$this->generate_enrolments(30, $module_delivery_key, 'student');
		$this->generate_enrolments(2, $module_delivery_key, 'convenor');
		$this->generate_enrolments(1, $module_delivery_key, 'teacher');

		$enrolments = \local_connect\enrolment::get_all(2014);

		$this->assertEquals(33, count($enrolments));

		// Add more.
		$this->generate_enrolments(30, $module_delivery_key, 'student');

		$enrolments = \local_connect\enrolment::get_all(2014);

		$this->assertEquals(63, count($enrolments));

		$this->connect_cleanup();
	}

	/**
	 * Make sure we can grab a valid list of enrolments for a specific course.
	 */
	public function test_enrolment_course_list() {
		global $CFG, $DB, $CONNECTDB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$module_delivery_key = $this->generate_course();
		$course = \local_connect\course::get_course_by_uid($module_delivery_key, $CFG->connect->session_code);
		$this->assertTrue($course->create_in_moodle());

		// Now, create another course.
		$module_delivery_key2 = $this->generate_course();
		$course2 = \local_connect\course::get_course_by_uid($module_delivery_key2, $CFG->connect->session_code);
		$this->assertTrue($course2->create_in_moodle());

		// Create an enrolment.
		$this->generate_enrolments(1, $module_delivery_key, 'teacher');
		$this->generate_enrolments(1, $module_delivery_key2, 'teacher');

		// Make sure we have two total
		$enrolments = \local_connect\enrolment::get_all(2014);
		$this->assertEquals(2, count($enrolments));

		// Make sure it worked.
		$enrolments = \local_connect\enrolment::get_enrolments_for_course($course);
		$this->assertEquals(1, count($enrolments));

		// Make sure it worked (2).
		$enrolments = \local_connect\enrolment::get_enrolments_for_course($course2);
		$this->assertEquals(1, count($enrolments));

		$this->connect_cleanup();
	}

	/**
	 * Make sure we can grab a valid list of enrolments for a specific user.
	 */
	public function test_enrolment_user_list() {
		global $CFG, $DB, $CONNECTDB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$module_delivery_key = $this->generate_course();
		$course = \local_connect\course::get_course_by_uid($module_delivery_key, $CFG->connect->session_code);
		$this->assertTrue($course->create_in_moodle());

		// Now, create another course.
		$module_delivery_key2 = $this->generate_course();
		$course2 = \local_connect\course::get_course_by_uid($module_delivery_key2, $CFG->connect->session_code);
		$this->assertTrue($course2->create_in_moodle());

		// Create an enrolment.
		$this->generate_enrolments(10, $module_delivery_key, 'student');
		$this->generate_enrolments(15, $module_delivery_key2, 'student');

		// Make sure we have two total
		$enrolments = \local_connect\enrolment::get_all(2014);
		$this->assertEquals(25, count($enrolments));

		// Select an enrolment.
		$enrolment = array_pop($enrolments);

		// Extract the user.
		$user = $DB->get_record('user', array(
			'id' => $enrolment->get_user_id()
		), 'id,username');

		// Make sure it worked.
		$enrolments = \local_connect\enrolment::get_enrolments_for_user($user->username);
		$this->assertEquals(1, count($enrolments));

		$this->connect_cleanup();
	}

	/**
	 * Make sure we can create an enrolment.
	 */
	public function test_enrolment_creation() {
		global $CFG, $DB, $CONNECTDB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$module_delivery_key = $this->generate_course();
		$course = \local_connect\course::get_course_by_uid($module_delivery_key, $CFG->connect->session_code);
		$this->assertTrue($course->create_in_moodle());

		// Create an enrolment.
		$this->generate_enrolments(1, $module_delivery_key, 'teacher');

		// Make sure it worked.
		$enrolments = \local_connect\enrolment::get_all(2014);
		$this->assertEquals(1, count($enrolments));

		$enrolment = array_pop($enrolments);
		$this->assertFalse($enrolment->is_in_moodle());
		$this->assertTrue($enrolment->create_in_moodle());
		$this->assertTrue($enrolment->is_in_moodle());

		$this->connect_cleanup();
	}

	/**
	 * Test enrolment validity check.
	 */
	public function test_enrolment_validity() {
		$enrolment = new \local_connect\enrolment(0, 1, 1, "test");
		$this->assertFalse($enrolment->is_valid());

		$enrolment = new \local_connect\enrolment(1, 0, 1, "test");
		$this->assertFalse($enrolment->is_valid());

		$enrolment = new \local_connect\enrolment(1, 1, 0, "test");
		$this->assertFalse($enrolment->is_valid());

		$enrolment = new \local_connect\enrolment(1, 1, 1, "test");
		$this->assertTrue($enrolment->is_valid());
	}
}