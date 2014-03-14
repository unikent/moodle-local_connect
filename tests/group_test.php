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
class kent_group_tests extends local_connect\util\connect_testcase
{
	/**
	 * Make sure we can grab a valid list of groups.
	 */
	public function test_groups_list() {
		global $CFG, $DB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// Create course.
		$course = \local_connect\course::get($this->generate_course());
		$course->create_in_moodle();

		// Test the global count.
		$groups = \local_connect\group::get_all();
		$this->assertEquals(0, count($groups));

		// Create a group.
		$this->generate_group($course->id);

		// Test the global count.
		$groups = \local_connect\group::get_all();
		$this->assertEquals(1, count($groups));

		// Create a group.
		$this->generate_groups(20, $course->id);

		// Test the global count.
		$groups = \local_connect\group::get_all();
		$this->assertEquals(21, count($groups));

		// Create another course.
		$course2 = \local_connect\course::get($this->generate_course());
		$course2->create_in_moodle();

		// Create a group.
		$this->generate_groups(20, $course2->id);

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
		global $CFG, $DB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$course = \local_connect\course::get($this->generate_course());
		$course->create_in_moodle();

		// Create a group.
		$groupid = $this->generate_group($course->id);

		// Get the group.
		$group = \local_connect\group::get($groupid);
		$this->assertEquals($groupid, $group->id);

		// Create it in Moodle.
		$this->assertFalse($group->is_in_moodle());
		$this->assertTrue($group->create_in_moodle());
		$this->assertTrue($group->is_in_moodle());

		$this->connect_cleanup();
	}

	/**
	 * Test user counting for groups.
	 */
	public function test_groups_counts() {
		global $CFG, $DB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$course = \local_connect\course::get($this->generate_course());
		$course->create_in_moodle();

		// Create a group.
		$group = $this->generate_group($course->id);
		$group = \local_connect\group::get($group);

		// Set some enrolments and test.
		$this->generate_group_enrolments(30, $group->id, 'student');
		$this->generate_group_enrolments(2, $group->id, 'teacher');

		$this->assertEquals(30, $group->count_students());
		$this->assertEquals(2, $group->count_staff());

		$this->generate_group_enrolments(2, $group->id, 'teacher');

		$this->assertEquals(30, $group->count_students());
		$this->assertEquals(4, $group->count_staff());

		// Add a different group.
		$course2 = \local_connect\course::get($this->generate_course());
		$course2->create_in_moodle();
		$group2 = $this->generate_group($course2->id);
		$group2 = \local_connect\group::get($group2);

		// Set some enrolments and test.
		$this->generate_group_enrolments(10, $group2->id, 'student');
		$this->generate_group_enrolments(1, $group2->id, 'convenor');
		$this->generate_group_enrolments(1, $group2->id, 'teacher');

		$this->assertEquals(12, $group2->count_all());
		$this->assertEquals(10, $group2->count_students());
		$this->assertEquals(2, $group2->count_staff());

		$this->connect_cleanup();
	}

	/**
	 * Make sure we can sync groups in Moodle.
	 */
	public function test_groups_sync() {
		global $CFG, $DB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// First, create a course.
		$course = \local_connect\course::get($this->generate_course());
		$course->create_in_moodle();

		// Create a group.
		$groupid = $this->generate_group($course->id);

		// Get the group.
		$group = \local_connect\group::get($groupid);
		$this->assertEquals($groupid, $group->id);

		// Sync it.
		$this->assertFalse($group->is_in_moodle());
		$this->assertEquals("Creating group: $group->id", $group->sync());
		$this->assertTrue($group->is_in_moodle());
		$this->assertEquals(null, $group->sync());

		// Check the Moodle name.
		$mgroup = $DB->get_record('groups', array(
            "id" => $group->mid
        ));
        $this->assertEquals($mgroup->name, $group->name);

        // Try changing the group name and synching it.
		$group->name = "TEST CHANGE";
		$mgroup = $DB->get_record('groups', array(
            "id" => $group->mid
        ));
        $this->assertNotEquals($mgroup->name, $group->name);
		$this->assertEquals("Updating group: $group->id", $group->sync());

		// Check the Moodle name again.
		$mgroup = $DB->get_record('groups', array(
            "id" => $group->mid
        ));
        $this->assertEquals($mgroup->name, $group->name);

		$this->connect_cleanup();
	}

	/**
	 * Test group observers.
	 */
	public function test_group_observer() {
		global $DB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		// Create course.
		$course = \local_connect\course::get($this->generate_course());
		$course->create_in_moodle();

		$group = \local_connect\group::get($this->generate_group($course->id));

		$this->assertFalse($group->is_in_moodle());
		$group->create_in_moodle();
		$this->assertTrue($group->is_in_moodle());

		// Delete from Moodle.
		groups_delete_group($group->mid);

		$group = \local_connect\group::get($group->id);
		$this->assertFalse($group->is_in_moodle());

		// Recreate in Moodle.
        $data = new \stdClass();
        $data->name = $group->name;
        $data->courseid = $course->mid;
        $data->description = '';
        groups_create_group($data);

		$group = \local_connect\group::get($group->id);
		$this->assertTrue($group->is_in_moodle());

		$this->connect_cleanup();
	}
}














