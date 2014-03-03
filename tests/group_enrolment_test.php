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
		$module_delivery_key = $this->generate_module_delivery_key();
		$module_delivery_key2 = $this->generate_module_delivery_key();

		// Create some groups.
		$this->generate_groups(20, $module_delivery_key);
		$this->generate_groups(10, $module_delivery_key2);
		$group = $this->generate_group($module_delivery_key);
		$group2 = $this->generate_group($module_delivery_key2);

		// Test the global count.
		$enrolments = \local_connect\group_enrolment::get_all($CFG->connect->session_code);
		$this->assertEquals(0, count($enrolments));

		// Generate a few enrolments.
		$this->generate_group_enrolments(30, $group, 'student');
		$this->generate_group_enrolments(2, $group, 'teacher');
		$this->generate_group_enrolments(20, $group2, 'student');
		$this->generate_group_enrolments(1, $group2, 'teacher');

		// Test the global count.
		$enrolments = \local_connect\group_enrolment::get_all($CFG->connect->session_code);
		$this->assertEquals(53, count($enrolments));

		// Test the group counter.
		$enrolments = \local_connect\group_enrolment::get_for_group(\local_connect\group::get($group['group_id']));
		$this->assertEquals(32, count($enrolments));
		$enrolments = \local_connect\group_enrolment::get_for_group(\local_connect\group::get($group2['group_id']));
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

		$module_delivery_key = $this->generate_module_delivery_key();
		$group = $this->generate_group($module_delivery_key);

		// Create the group in Moodle
		{
			$obj = \local_connect\group::get($group['group_id']);
			$obj->create_in_moodle();
		}

		$enrolment = $this->generate_group_enrolment($group, 'teacher');

		// Create the enrolment in Moodle
		{
			$obj = \local_connect\enrolment::get($module_delivery_key, $CFG->connect->session_code, $enrolment['login']);
			$obj->create_in_moodle();
		}

		$obj = \local_connect\group_enrolment::get($enrolment['group_id'], $enrolment['login']);
		$this->assertFalse($obj->is_in_moodle());
		$this->assertTrue($obj->create_in_moodle());
		$this->assertTrue($obj->is_in_moodle());

		$this->connect_cleanup();
	}

	/**
	 * Make sure we can delete a group enrolment in Moodle.
	 */
	public function test_group_enrolment_delete() {
		global $CFG;

		$this->resetAfterTest();
		$this->connect_cleanup();

		$module_delivery_key = $this->generate_module_delivery_key();
		$group = $this->generate_group($module_delivery_key);

		// Create the group in Moodle
		{
			$obj = \local_connect\group::get($group['group_id']);
			$obj->create_in_moodle();
		}

		$enrolment = $this->generate_group_enrolment($group, 'teacher');

		// Create the enrolment in Moodle
		{
			$obj = \local_connect\enrolment::get($module_delivery_key, $CFG->connect->session_code, $enrolment['login']);
			$obj->create_in_moodle();
		}

		$obj = \local_connect\group_enrolment::get($enrolment['group_id'], $enrolment['login']);
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
		global $CFG, $CONNECTDB;

		$this->resetAfterTest();
		$this->connect_cleanup();

		$module_delivery_key = $this->generate_module_delivery_key();
		$group = $this->generate_group($module_delivery_key);

		// Create the group in Moodle
		{
			$obj = \local_connect\group::get($group['group_id']);
			$obj->create_in_moodle();
		}

		$enrolment = $this->generate_group_enrolment($group, 'teacher');

		// Create the enrolment in Moodle
		{
			$obj = \local_connect\enrolment::get($module_delivery_key, $CFG->connect->session_code, $enrolment['login']);
			$obj->create_in_moodle();
		}

		$obj = \local_connect\group_enrolment::get($enrolment['group_id'], $enrolment['login']);
		$this->assertFalse($obj->is_in_moodle());
		$this->assertEquals("Creating Group Enrollment: {$obj->chksum}", $obj->sync());
		$this->assertTrue($obj->is_in_moodle());

		$CONNECTDB->set_field('group_enrollments', 'sink_deleted', 1, array(
			'chksum' => $obj->chksum,
            "group_id" => $enrolment['group_id'],
            "login" => $enrolment['login']
		));

		$obj = \local_connect\group_enrolment::get($enrolment['group_id'], $enrolment['login']);
		$this->assertTrue($obj->is_in_moodle());
		$this->assertEquals("Deleting Group Enrollment: {$obj->chksum}", $obj->sync());
		$this->assertFalse($obj->is_in_moodle());

		$this->connect_cleanup();
	}
}