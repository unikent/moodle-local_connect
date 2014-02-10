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
class kent_enrolment_tests extends local_connect\tests\connect_testcase
{
	/**
	 * Make sure we can grab a valid list of enrolments.
	 */
	public function test_enrolment_list() {
		global $CONNECTDB;

		$CONNECTDB->execute("TRUNCATE TABLE {enrollments}");
		$CONNECTDB->execute("TRUNCATE TABLE {courses}");

		// First, create a course.
		$module_delivery_key = $this->generate_course();

		// Next insert a couple of enrolments on this course.
		$this->generate_enrolments(30, $module_delivery_key, 'student');
		$this->generate_enrolments(2,  $module_delivery_key, 'convenor');
		$this->generate_enrolments(1,  $module_delivery_key, 'teacher');

		$enrolments = \local_connect\enrolment::get_all(2014);

		$this->assertEquals(33, count($enrolments));
	}
}