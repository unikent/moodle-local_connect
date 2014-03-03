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
		global $CFG, $DB, $CONNECTDB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$module_delivery_key = $this->generate_module_delivery_key();

		// Test the global count.
		$enrolments = \local_connect\enrolment::get_all($CFG->connect->session_code);
		$this->assertEquals(0, count($enrolments));

		// Next insert a couple of enrolments on this course.
		$this->generate_enrolments(30, $module_delivery_key, 'student');
		$this->generate_enrolments(2, $module_delivery_key, 'convenor');
		$this->generate_enrolments(1, $module_delivery_key, 'teacher');

		// Test the global count.
		$enrolments = \local_connect\enrolment::get_all($CFG->connect->session_code);
		$this->assertEquals(33, count($enrolments));

		// Add more.
		$this->generate_enrolments(30, $module_delivery_key, 'student');

		// Test the global count.
		$enrolments = \local_connect\enrolment::get_all($CFG->connect->session_code);
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
		$module_delivery_key = $this->generate_module_delivery_key();
		$course = \local_connect\course::get_course_by_uid($module_delivery_key, $CFG->connect->session_code);

		// Now, create another course.
		$module_delivery_key2 = $this->generate_module_delivery_key();
		$course2 = \local_connect\course::get_course_by_uid($module_delivery_key2, $CFG->connect->session_code);

		// Create an enrolment.
		$this->generate_enrolments(1, $module_delivery_key, 'teacher');
		$this->generate_enrolments(1, $module_delivery_key2, 'teacher');

		// Make sure we have two total
		$enrolments = \local_connect\enrolment::get_all($CFG->connect->session_code);
		$this->assertEquals(2, count($enrolments));

		// Make sure it worked.
		$enrolments = \local_connect\enrolment::get_for_course($course);
		$this->assertEquals(1, count($enrolments));

		// Make sure it worked (2).
		$enrolments = \local_connect\enrolment::get_for_course($course2);
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
		$module_delivery_key = $this->generate_module_delivery_key();

		// Now, create another course.
		$module_delivery_key2 = $this->generate_module_delivery_key();

		// Create an enrolment.
		$this->generate_enrolments(10, $module_delivery_key, 'student');
		$this->generate_enrolments(15, $module_delivery_key2, 'student');

		// Make sure we have two total
		$enrolments = \local_connect\enrolment::get_all($CFG->connect->session_code);
		$this->assertEquals(25, count($enrolments));

		// Select an enrolment.
		$enrolment = array_pop($enrolments);

		// Extract the user.
		$user = $DB->get_record('user', array(
			'id' => $enrolment->get_user_id()
		), 'id,username');

		// Make sure it worked.
		$enrolments = \local_connect\enrolment::get_for_user($user->username);
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
		$module_delivery_key = $this->generate_module_delivery_key();

		// Create an enrolment.
		$this->generate_enrolment($module_delivery_key, 'teacher');

		// Make sure it worked.
		$enrolments = \local_connect\enrolment::get_all($CFG->connect->session_code);
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

	/**
	 * Test the observers.
	 */
	public function test_observer() {
		global $CFG, $DB, $CONNECTDB;

		require_once($CFG->dirroot.'/user/lib.php');

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$module_delivery_key = $this->generate_module_delivery_key();

		// Create an enrolment without a user, create the user, check they were enrolled.
		$enrolment = $this->generate_enrolment($module_delivery_key, 'student');
		$record = array(
			"username" => $enrolment['login']
		);

		// Grab it (Bit crude, I know).
		$enrolments = \local_connect\enrolment::get_all($CFG->connect->session_code);
		$this->assertEquals(1, count($enrolments));
		$enrolment = array_pop($enrolments);

		// Create the enrolment.
		$this->assertFalse($enrolment->is_in_moodle());
		$enrolment->create_in_moodle();
		$this->assertTrue($enrolment->is_in_moodle());

		// Delete the user.
		$user = $DB->get_record('user', $record);
		user_delete_user($user);

		$this->assertFalse($enrolment->is_in_moodle());

		// Now create the user (properly - otherwise the observer wont be called).
		user_create_user(array(
			'username' => $record['username'],
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

		$enrolments = \local_connect\enrolment::get_for_user($record['username']);
		$this->assertEquals(1, count($enrolments));
		$enrolment = array_pop($enrolments);

		// Did the enrolment get created?
		$this->assertTrue($enrolment->is_in_moodle());

		$this->connect_cleanup();
	}

	/**
	 * Test an enrolment deletion.
	 */
	public function test_deletion() {
		global $CFG;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$module_delivery_key = $this->generate_module_delivery_key();

		// Create an enrolment.
		$this->generate_enrolments(10, $module_delivery_key, 'student');

		// Make sure it worked.
		$enrolments = \local_connect\enrolment::get_all($CFG->connect->session_code);
		$this->assertEquals(10, count($enrolments));

		$enrolment = array_pop($enrolments);
		$this->assertFalse($enrolment->is_in_moodle());
		$this->assertTrue($enrolment->create_in_moodle());
		$this->assertTrue($enrolment->is_in_moodle());
		$enrolment->delete();
		$this->assertFalse($enrolment->is_in_moodle());

		// Re-test counts to make sure connect wasnt affected.
		$enrolments = \local_connect\enrolment::get_all($CFG->connect->session_code);
		$this->assertEquals(10, count($enrolments));

		$this->connect_cleanup();
	}

	/**
	 * Test we can still work with non-connect users.
	 */
	public function test_non_connect_users() {
		global $CFG;

		$this->resetAfterTest();
		$this->connect_cleanup();

		$user = $this->getDataGenerator()->create_user();
		$this->assertEquals(0, count(\local_connect\enrolment::get_for_user($user->username)));

		$this->assertEquals(0, count(\local_connect\enrolment::get_for_user("RandomUser")));

		$this->connect_cleanup();
	}

	/**
	 * Make sure we can sync properly.
	 */
	public function test_enrolment_sync() {
		global $CFG, $CONNECTDB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$module_delivery_key = $this->generate_module_delivery_key();

		// Create an enrolment.
		$this->generate_enrolment($module_delivery_key, 'teacher');

		// Make sure it worked.
		$enrolments = \local_connect\enrolment::get_all($CFG->connect->session_code);
		$this->assertEquals(1, count($enrolments));

		$enrolment = array_pop($enrolments);
		$this->assertFalse($enrolment->is_in_moodle());
		$this->assertTrue($enrolment->sync());
		$this->assertTrue($enrolment->is_in_moodle());

		$this->connect_cleanup();
	}
}