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
class kent_group_enrolment_tests extends local_connect\tests\connect_testcase
{
	/**
	 * Make sure we can grab a valid list of enrolments.
	 */
	public function test_group_enrolment_list() {
		global $CFG, $DB, $CONNECTDB;

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

		$this->connect_cleanup();
	}
}