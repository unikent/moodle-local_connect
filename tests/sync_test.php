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
 * Tests new Kent sync code
 */
class kent_sync_tests extends local_connect\util\connect_testcase
{
	/**
	 * Make sure we can grab a valid list of enrolments to be created.
	 */
	public function test_enrolment_creates() {
		global $CFG, $DB;

		$this->resetAfterTest();

		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		$this->assertEquals(array(), \local_connect\sync::get_deleted_enrolments());

		// Create some fake enrolments.
		$this->generate_enrolments(30, $course, 'student');
		$this->generate_enrolments(2, $course, 'convenor');
		$this->generate_enrolments(1, $course, 'teacher');

		$this->assertEquals(33, count(\local_connect\sync::get_connect_enrolments()));
		$this->assertEquals(0, count(\local_connect\sync::get_moodle_enrolments()));
		$this->assertEquals(33, count(\local_connect\sync::get_new_enrolments()));

		$enrolments = \local_connect\enrolment::get_all();
		foreach ($enrolments as $enrolment) {
			$enrolment->create_in_moodle();
		}

		$this->assertEquals(0, count(\local_connect\sync::get_new_enrolments()));
	}

	/**
	 * Make sure we can grab a valid list of enrolments to be deleted.
	 */
	public function test_enrolment_deletes() {
		global $CFG, $DB;

		$this->resetAfterTest();

		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		$this->assertEquals(array(), \local_connect\sync::get_deleted_enrolments());

		// Create some fake enrolments.
		$this->generate_enrolments(30, $course, 'student');
		$this->generate_enrolments(2, $course, 'convenor');
		$teacher = $this->generate_enrolment(1, $course, 'teacher');
		$teacher = $DB->get_record('connect_enrolments', array("id" => $teacher));

		$enrolments = \local_connect\enrolment::get_all();
		foreach ($enrolments as $enrolment) {
			$enrolment->create_in_moodle();
		}

		$this->assertEquals(array(), \local_connect\sync::get_deleted_enrolments());

		// Now mark one for deletion.
		$teacher->deleted = 1;
		$DB->update_record('connect_enrolments', $teacher);

		$this->assertEquals(array($teacher->id), \local_connect\sync::get_deleted_enrolments());
	}
}