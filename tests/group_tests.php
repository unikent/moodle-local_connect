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
 * Tests new Kent group code
 */
class kent_group_tests extends local_connect\tests\connect_testcase
{
	/**
	 * Make sure we can grab a valid list of enrolments.
	 */
	public function test_groups_list() {
		global $CFG, $DB, $CONNECTDB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$course = $this->generate_course();
		$module_delivery_key = $course['module_delivery_key'];

		// And in Moodle.
		$course = \local_connect\course::get_course_by_uid($module_delivery_key, $CFG->connect->session_code);
		$this->assertTrue($course->create_in_moodle());

		// Test the global count.
		$groups = \local_connect\group::get_all($CFG->connect->session_code);
		$this->assertEquals(0, count($groups));

		// Create a group.
		$this->generate_group($module_delivery_key);

		// Test the global count.
		$groups = \local_connect\group::get_all($CFG->connect->session_code);
		$this->assertEquals(1, count($groups));

		// Create a group.
		$this->generate_groups(20, $module_delivery_key);

		// Test the global count.
		$groups = \local_connect\group::get_all($CFG->connect->session_code);
		$this->assertEquals(21, count($groups));

		$this->connect_cleanup();
	}
}














