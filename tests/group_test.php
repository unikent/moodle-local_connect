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
	 * Make sure we can grab a valid list of groups.
	 */
	public function test_groups_list() {
		global $CFG, $DB, $CONNECTDB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$module_delivery_key = $this->generate_module_delivery_key();

		// And in Moodle.
		$course = \local_connect\course::get_course_by_uid($module_delivery_key, $CFG->connect->session_code);

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

		// Create another course.
		$module_delivery_key2 = $this->generate_module_delivery_key();
		$course2 = \local_connect\course::get_course_by_uid($module_delivery_key2, $CFG->connect->session_code);

		// Create a group.
		$this->generate_groups(20, $module_delivery_key2);

		// Test the course count.
		$groups = \local_connect\group::get_for_course($course);
		$this->assertEquals(21, count($groups));

		// Test the course count.
		$groups = \local_connect\group::get_for_course($course2);
		$this->assertEquals(20, count($groups));

		$this->connect_cleanup();
	}

	/**
	 * Make sure we can create groups in Moodle.
	 */
	public function test_groups_create() {
		global $CFG, $DB, $CONNECTDB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$module_delivery_key = $this->generate_module_delivery_key();

		// Create a group.
		$data = $this->generate_group($module_delivery_key);

		// Get the group.
		$group = \local_connect\group::get($data['group_id']);
		$this->assertEquals($data['group_id'], $group->id);

		// Create it in Moodle.
		$this->assertFalse($group->is_in_moodle());
		$this->assertTrue($group->create_in_moodle());
		$this->assertTrue($group->is_in_moodle());

		$this->connect_cleanup();
	}
}














