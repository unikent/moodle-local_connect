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
class kent_enrolment_tests extends local_connect\util\connect_testcase
{
	/**
	 * Make sure we can grab a valid list of enrolments.
	 */
	public function test_enrolment_list() {
		global $CFG, $DB;

		$this->resetAfterTest();

		// First, create a course.
		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		// Test the global count.
		$enrolments = \local_connect\enrolment::get_all();
		$this->assertEquals(0, count($enrolments));

		// Next insert a couple of enrolments on this course.
		$this->generate_enrolments(30, $course, 'student');
		$this->generate_enrolments(2, $course, 'convenor');
		$this->generate_enrolments(1, $course, 'teacher');

		// Test the global count.
		$enrolments = \local_connect\enrolment::get_all();
		$this->assertEquals(33, count($enrolments));

		// Add more.
		$this->generate_enrolments(30, $course, 'student');

		// Test the global count.
		$enrolments = \local_connect\enrolment::get_all();
		$this->assertEquals(63, count($enrolments));

	}

	/**
	 * Make sure we can grab a valid list of enrolments for a specific course.
	 */
	public function test_enrolment_course_list() {
		global $CFG, $DB;

		$this->resetAfterTest();

		// First, create a course.
		$course = $this->generate_course();
		$course = \local_connect\course::get($course);
		$course->create_in_moodle();

		// Now, create another course.
		$course2 = $this->generate_course();
		$course2 = \local_connect\course::get($course2);
		$course2->create_in_moodle();

		// Create an enrolment.
		$this->generate_enrolments(1, $course->id, 'teacher');
		$this->generate_enrolments(1, $course2->id, 'teacher');

		// Make sure we have two total
		$enrolments = \local_connect\enrolment::get_all();
		$this->assertEquals(2, count($enrolments));

		// Make sure it worked.
		$enrolments = \local_connect\enrolment::get_for_course($course);
		$this->assertEquals(1, count($enrolments));

		// Make sure it worked (2).
		$enrolments = \local_connect\enrolment::get_for_course($course2);
		$this->assertEquals(1, count($enrolments));

	}

	/**
	 * Make sure we can grab a valid list of enrolments for a specific user.
	 */
	public function test_enrolment_user_list() {
		global $CFG, $DB;

		$this->resetAfterTest();

		// First, create a course.
		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		// Now, create another course.
		$course2 = $this->generate_course();
		$course_obj = \local_connect\course::get($course2);
		$course_obj->create_in_moodle();

		// Create an enrolment.
		$this->generate_enrolments(10, $course, 'student');
		$this->generate_enrolments(15, $course2, 'student');

		// Make sure we have two total
		$enrolments = \local_connect\enrolment::get_all();
		$this->assertEquals(25, count($enrolments));

		// Select an enrolment.
		$enrolment = array_pop($enrolments);

		// Make sure it worked.
		$user = $enrolment->user;
		$enrolments = \local_connect\enrolment::get_for_user($user);
		$this->assertEquals(1, count($enrolments));

	}

	/**
	 * Make sure we can create an enrolment.
	 */
	public function test_enrolment_creation() {
		global $CFG, $DB;

		$this->resetAfterTest();

		// First, create a course.
		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		// Create an enrolment.
		$this->generate_enrolment($course, 'teacher');

		// Make sure it worked.
		$enrolments = \local_connect\enrolment::get_all();
		$this->assertEquals(1, count($enrolments));

		$enrolment = array_pop($enrolments);
		$this->assertFalse($enrolment->is_in_moodle());
		$this->assertTrue($enrolment->create_in_moodle());
		$this->assertTrue($enrolment->is_in_moodle());
	}

	/**
	 * Test the observers.
	 */
	public function test_observer() {
		global $CFG, $DB;

		require_once($CFG->dirroot.'/user/lib.php');

		$this->resetAfterTest();

		// First, create a course.
		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		// Create an enrolment without a user, create the user, check they were enrolled.
		$enrolment = $this->generate_enrolment($course, 'student');
		$enrolment = \local_connect\enrolment::get($enrolment);
		$user = $DB->get_record('user', array(
			"id" => $enrolment->user->mid
		));

		// Create the enrolment.
		$this->assertFalse($enrolment->is_in_moodle());
		$enrolment->create_in_moodle();
		$this->assertTrue($enrolment->is_in_moodle());

		// Delete the user.
		user_delete_user($user);

		// Did the enrolment get deleted?
		$this->assertFalse($enrolment->is_in_moodle());

		// Now create the user (properly - otherwise the observer wont be called).
		user_create_user(array(
			'username' => $user->username,
			'password' => 'Moodle2012!',
			'idnumber' => 'idnumbertest1',
			'firstname' => 'First Name',
			'lastname' => 'Last Name',
			'middlename' => 'Middle Name',
			'lastnamephonetic' => '',
			'firstnamephonetic' => '',
			'alternatename' => 'Alternate Name',
			'email' => 'usertest1@email.com',
			'description' => 'This is a description for user 1',
			'city' => 'Canterbury',
			'country' => 'uk'
		));

		// Did the enrolment get created?
		$this->assertTrue($enrolment->is_in_moodle());
	}

	/**
	 * Test an enrolment deletion.
	 */
	public function test_deletion() {
		global $CFG;

		$this->resetAfterTest();

		// First, create a course.
		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		// Create an enrolment.
		$this->generate_enrolments(10, $course, 'student');

		// Make sure it worked.
		$enrolments = \local_connect\enrolment::get_all();
		$this->assertEquals(10, count($enrolments));

		$enrolment = array_pop($enrolments);
		$this->assertFalse($enrolment->is_in_moodle());
		$this->assertTrue($enrolment->create_in_moodle());
		$this->assertTrue($enrolment->is_in_moodle());
		$enrolment->delete();
		$this->assertFalse($enrolment->is_in_moodle());

		// Re-test counts to make sure connect wasnt affected.
		$enrolments = \local_connect\enrolment::get_all();
		$this->assertEquals(10, count($enrolments));

	}

	/**
	 * Test we can still work with non-connect users.
	 */
	public function test_non_connect_users() {
		global $CFG;

		$this->resetAfterTest();

		$this->assertEquals(0, count(\local_connect\enrolment::get_for_user(null)));

	}

	/**
	 * Make sure we can sync properly.
	 */
	public function test_enrolment_sync() {
		global $CFG;

		$this->resetAfterTest();

		// First, create a course.
		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		// Create an enrolment.
		$this->generate_enrolment($course, 'teacher');

		// Make sure it worked.
		$enrolments = \local_connect\enrolment::get_all();
		$this->assertEquals(1, count($enrolments));

		$enrolment = array_pop($enrolments);
		$this->assertFalse($enrolment->is_in_moodle());
		$this->assertEquals("Creating Enrolment: {$enrolment->id}", $enrolment->sync());
		$this->assertTrue($enrolment->is_in_moodle());

	}
}