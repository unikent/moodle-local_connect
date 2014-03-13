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
class kent_user_tests extends local_connect\util\connect_testcase
{
	/**
	 * Make sure we can create a Moodle user.
	 */
	public function test_user_create() {
		global $CFG, $DB, $CONNECTDB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		$userid = $this->generate_user();

		$user = \local_connect\user::get($userid);
		$this->assertTrue($user->is_in_moodle());
		$user->create_in_moodle();
		$this->assertTrue($user->is_in_moodle());
		$user->delete();
		$this->assertFalse($user->is_in_moodle());
		$user->create_in_moodle();
		$this->assertTrue($user->is_in_moodle());

		$this->connect_cleanup();
	}

	/**
	 * Make sure we can get users by roles.
	 */
	public function test_user_get_by_role() {
		global $CFG, $DB, $CONNECTDB;

		$this->resetAfterTest();
		$this->connect_cleanup();

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

		$this->connect_cleanup();
	}
}