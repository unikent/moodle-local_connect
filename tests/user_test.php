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

		$module_delivery_key = $this->generate_module_delivery_key();
		$enrolment = $this->generate_enrolment($module_delivery_key, 'student');

		$user = \local_connect\user::get($enrolment["login"]);
		$this->assertTrue($user->is_in_moodle());
		$user->create_in_moodle();
		$this->assertTrue($user->is_in_moodle());
		$user->delete();
		$this->assertFalse($user->is_in_moodle());
		$user->create_in_moodle();
		$this->assertTrue($user->is_in_moodle());

		$this->connect_cleanup();
	}
}