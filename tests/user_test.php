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
 * Tests new Kent user code
 */
class kent_user_tests extends \local_connect\tests\connect_testcase
{
	/**
	 * Make sure we can create a Moodle user.
	 */
	public function test_user_create() {
		global $CFG, $DB;

		$this->resetAfterTest();

		$userid = $this->generate_user();

		$user = \local_connect\user::get($userid);
		$this->assertTrue($user->is_in_moodle());
		$user->create_in_moodle();
		$this->assertTrue($user->is_in_moodle());
		$user->delete();
		$this->assertFalse($user->is_in_moodle());
		$user->create_in_moodle();
		$this->assertTrue($user->is_in_moodle());

	}

	/**
	 * Make sure we can get users by roles.
	 */
	public function test_user_get_by_role() {
		$this->resetAfterTest();

		$this->assertEquals(0, count(\local_connect\user::get_by_role("student")));
		$this->assertEquals(0, count(\local_connect\user::get_by_role("staff")));
		$this->assertEquals(0, count(\local_connect\user::get_by_role("teacher")));
		$this->assertEquals(0, count(\local_connect\user::get_by_role("convenor")));

		{
			$course = $this->generate_course();
			$this->generate_enrolments(50, $course, 'student');
			$this->generate_enrolments(2, $course, 'teacher');
			$this->generate_enrolments(4, $course, 'convenor');
		}

		$this->assertEquals(50, count(\local_connect\user::get_by_role("student")));
		$this->assertEquals(6, count(\local_connect\user::get_by_role("staff")));
		$this->assertEquals(2, count(\local_connect\user::get_by_role("teacher")));
		$this->assertEquals(4, count(\local_connect\user::get_by_role("convenor")));

		{
			$course = $this->generate_course();
			$this->generate_enrolments(100, $course, 'student');
			$this->generate_enrolments(4, $course, 'teacher');
			$this->generate_enrolments(8, $course, 'convenor');
		}

		$this->assertEquals(150, count(\local_connect\user::get_by_role("student")));
		$this->assertEquals(18, count(\local_connect\user::get_by_role("staff")));
		$this->assertEquals(6, count(\local_connect\user::get_by_role("teacher")));
		$this->assertEquals(12, count(\local_connect\user::get_by_role("convenor")));

	}

	/**
	 * Test user observers.
	 */
	public function test_user_observer() {
		global $DB;

		$this->resetAfterTest();

		$user = $this->generate_user();
		$user = \local_connect\user::get($user);

		$this->assertTrue($user->is_in_moodle());

		user_delete_user($DB->get_record('user', array(
			'id' => $user->mid
		)));

		$user = \local_connect\user::get($user->id);
		$this->assertFalse($user->is_in_moodle());

		user_create_user(array(
			'username' => $user->login,
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

		$user = \local_connect\user::get($user->id);
		$this->assertTrue($user->is_in_moodle());

	}
}