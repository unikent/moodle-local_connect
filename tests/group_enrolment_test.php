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
class kent_group_enrolment_tests extends local_connect\util\connect_testcase
{
	/**
	 * Make sure we can grab a valid list of enrolments.
	 */
	public function test_group_enrolment_list() {
		global $CFG;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// Grab some delivery keys.
		$course = $this->generate_course();
		$course2 = $this->generate_course();

		// Create some groups.
		$this->generate_groups(20, $course);
		$this->generate_groups(10, $course2);
		$group = $this->generate_group($course);
		$group2 = $this->generate_group($course2);

		// Test the global count.
		$enrolments = \local_connect\group_enrolment::get_all();
		$this->assertEquals(0, count($enrolments));

		// Generate a few enrolments.
		$this->generate_group_enrolments(30, $group, 'student');
		$this->generate_group_enrolments(2, $group, 'teacher');
		$this->generate_group_enrolments(20, $group2, 'student');
		$this->generate_group_enrolments(1, $group2, 'teacher');

		// Test the global count.
		$enrolments = \local_connect\group_enrolment::get_all();
		$this->assertEquals(53, count($enrolments));

		// Test the group counter.
		$enrolments = \local_connect\group_enrolment::get_for_group(\local_connect\group::get($group));
		$this->assertEquals(32, count($enrolments));
		$enrolments = \local_connect\group_enrolment::get_for_group(\local_connect\group::get($group2));
		$this->assertEquals(21, count($enrolments));

		$this->connect_cleanup();
	}

	/**
	 * Make sure we can create a valid group enrolment in Moodle.
	 */
	public function test_group_enrolment_create() {
		global $CFG;

		$this->resetAfterTest();
		$this->connect_cleanup();

		$course = $this->generate_course();

		// Create the course in Moodle
		{
			$obj = \local_connect\course::get($course);
			$obj->create_in_moodle();
		}

		$group = $this->generate_group($course);

		// Create the group in Moodle
		{
			$obj = \local_connect\group::get($group);
			$obj->create_in_moodle();
		}

		$ge = $this->generate_group_enrolment($group, 'teacher');

		$obj = \local_connect\group_enrolment::get($ge);
		$this->assertFalse($obj->is_in_moodle());
		$this->assertTrue($obj->create_in_moodle());
		$this->assertTrue($obj->is_in_moodle());

		$this->connect_cleanup();
	}

	/**
	 * Make sure we can delete a group enrolment in Moodle.
	 */
	public function test_group_enrolment_delete() {
		$this->resetAfterTest();
		$this->connect_cleanup();

		$course = $this->generate_course();

		// Create the course in Moodle
		{
			$obj = \local_connect\course::get($course);
			$obj->create_in_moodle();
		}

		$group = $this->generate_group($course);

		// Create the group in Moodle
		{
			$obj = \local_connect\group::get($group);
			$obj->create_in_moodle();
		}

		$ge = $this->generate_group_enrolment($group, 'teacher');

		$obj = \local_connect\group_enrolment::get($ge);
		$this->assertFalse($obj->is_in_moodle());
		$this->assertTrue($obj->create_in_moodle());
		$this->assertTrue($obj->is_in_moodle());
		$obj->delete();
		$this->assertFalse($obj->is_in_moodle());

		$this->connect_cleanup();
	}

	/**
	 * Make sure we can sync properly.
	 */
	public function test_group_enrolment_sync() {
		global $DB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		$course = $this->generate_course();

		// Create the course in Moodle
		{
			$obj = \local_connect\course::get($course);
			$obj->create_in_moodle();
		}

		$group = $this->generate_group($course);

		// Create the group in Moodle
		{
			$obj = \local_connect\group::get($group);
			$obj->create_in_moodle();
		}

		$ge = $this->generate_group_enrolment($group, 'teacher');

		$obj = \local_connect\group_enrolment::get($ge);
		$this->assertFalse($obj->is_in_moodle());
		$this->assertEquals("Creating Group Enrolment: {$obj->id}", $obj->sync());
		$this->assertTrue($obj->is_in_moodle());

		$obj->deleted = 1;

		$this->assertTrue($obj->is_in_moodle());
		$this->assertEquals("Deleting Group Enrolment: {$obj->id}", $obj->sync());
		$this->assertFalse($obj->is_in_moodle());

		$this->connect_cleanup();
	}
}