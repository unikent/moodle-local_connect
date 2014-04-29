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
		$teacher = $this->generate_enrolment($course, 'teacher');
		$teacher_obj = \local_connect\enrolment::get($teacher);

		$this->assertEquals(33, count(\local_connect\sync::get_connect_enrolments()));
		$this->assertEquals(0, count(\local_connect\sync::get_moodle_enrolments(0, 1000)));
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

	/**
	 * Make sure we can grab a valid list of enrolments to be created when they have changed role.
	 */
	public function test_enrolment_changes() {
		global $CFG, $DB;

		$this->resetAfterTest();

		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		// Create some fake enrolments.
		$this->generate_enrolments(30, $course, 'student');
		$this->generate_enrolments(2, $course, 'convenor');
		$teacher = $this->generate_enrolment($course, 'teacher');
		$teacher_obj = \local_connect\enrolment::get($teacher);

		$enrolments = \local_connect\enrolment::get_all();
		foreach ($enrolments as $enrolment) {
			$enrolment->create_in_moodle();
		}

		$this->assertEquals(array(), \local_connect\sync::get_new_enrolments());
		$this->assertEquals(array(), \local_connect\sync::get_changed_enrolments());
		$this->assertEquals(array(), \local_connect\sync::get_deleted_enrolments());

		// Now change the teacher enrolment to a student.
		$teacher_obj->roleid = $role = $DB->get_field("connect_role", "id", array("name" => "student"));
		$teacher_obj->save();

		// Technically, this is also new enrolment.
		$this->assertEquals(array($teacher), \local_connect\sync::get_new_enrolments());
		$this->assertEquals(array($teacher), \local_connect\sync::get_changed_enrolments());
		$this->assertEquals(array(), \local_connect\sync::get_deleted_enrolments());
	}

	/**
	 * Make sure we can grab a valid list of extra enrolments (in Moodle, but not SDS).
	 */
	public function test_enrolment_extras() {
		global $CFG, $DB;

		$this->resetAfterTest();

		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		// Create some fake enrolments.
		$this->generate_enrolments(30, $course, 'student');
		$this->generate_enrolments(2, $course, 'convenor');
		$teacher = $this->generate_enrolment($course, 'teacher');

		$enrolments = \local_connect\enrolment::get_all();
		foreach ($enrolments as $enrolment) {
			$enrolment->create_in_moodle();
		}

		$this->assertEquals(array(), \local_connect\sync::get_new_enrolments());
		$this->assertEquals(array(), \local_connect\sync::get_deleted_enrolments());
		$this->assertEquals(array(), \local_connect\sync::get_changed_enrolments());
		$this->assertEquals(array(), \local_connect\sync::get_extra_enrolments());

		// Now delete the teacher.
		$DB->delete_records('connect_enrolments', array(
			"id" => $teacher
		));

		// Assert!
		$this->assertEquals(array(), \local_connect\sync::get_new_enrolments());
		$this->assertEquals(array(), \local_connect\sync::get_changed_enrolments());
		$this->assertEquals(array(), \local_connect\sync::get_deleted_enrolments());
		$this->assertEquals(1, count(\local_connect\sync::get_extra_enrolments()));
	}

	/**
	 * Test group creates.
	 */
	public function test_group_creates() {
		global $CFG, $DB;

		$this->resetAfterTest();

		$this->assertEquals(array(), \local_connect\sync::get_new_groups());

		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		$this->generate_groups(20, $course);

		$this->assertEquals(20, count(\local_connect\sync::get_new_groups()));

		foreach (\local_connect\group::get_all() as $group) {
			$group->create_in_moodle();
		}

		$this->assertEquals(array(), \local_connect\sync::get_new_groups());
	}

	/**
	 * Test group enrolment creates.
	 */
	public function test_group_enrolment_creates() {
		global $CFG, $DB;

		$this->resetAfterTest();

		$this->assertEquals(array(), \local_connect\sync::get_new_group_enrolments());

		// Grab us a course.
		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		// Create a group.
		$group = $this->generate_group($course);
		$group_obj = \local_connect\group::get($group);
		$group_obj->create_in_moodle();

		$this->assertEquals(array(), \local_connect\sync::get_new_group_enrolments());

		// Generate a few enrolments.
		$this->generate_group_enrolments(30, $group, 'student');
		$this->generate_group_enrolments(2, $group, 'teacher');

		$this->assertEquals(32, count(\local_connect\sync::get_new_group_enrolments()));

		foreach (\local_connect\group_enrolment::get_all() as $enrolment) {
			$enrolment->create_in_moodle();
		}

		$this->assertEquals(array(), \local_connect\sync::get_new_group_enrolments());

		// Also check we dont register deleted ones..
		$teacher = $this->generate_group_enrolment($group, 'teacher');

		$this->assertEquals(array($teacher), \local_connect\sync::get_new_group_enrolments());

		$DB->update_record('connect_group_enrolments', array(
			"id" => $teacher,
			"deleted" => 1
		));

		$this->assertEquals(array(), \local_connect\sync::get_new_group_enrolments());
	}

	/**
	 * Test group enrolment deletes.
	 */
	public function test_group_enrolment_deletes() {
		global $CFG, $DB;

		$this->resetAfterTest();

		$this->assertEquals(array(), \local_connect\sync::get_new_group_enrolments());
		$this->assertEquals(array(), \local_connect\sync::get_deleted_group_enrolments());

		// Grab us a course.
		$course = $this->generate_course();
		$course_obj = \local_connect\course::get($course);
		$course_obj->create_in_moodle();

		// Create a group.
		$group = $this->generate_group($course);
		$group_obj = \local_connect\group::get($group);
		$group_obj->create_in_moodle();

		$this->assertEquals(array(), \local_connect\sync::get_new_group_enrolments());
		$this->assertEquals(array(), \local_connect\sync::get_deleted_group_enrolments());

		// Generate a few enrolments.
		$this->generate_group_enrolments(30, $group, 'student');
		$teacher = $this->generate_group_enrolment($group, 'teacher');
		$teacher_obj = \local_connect\group_enrolment::get($teacher);

		$this->assertEquals(31, count(\local_connect\sync::get_new_group_enrolments()));

		foreach (\local_connect\group_enrolment::get_all() as $enrolment) {
			$enrolment->create_in_moodle();
		}

		$this->assertEquals(array(), \local_connect\sync::get_new_group_enrolments());
		$this->assertEquals(array(), \local_connect\sync::get_deleted_group_enrolments());

		// Delete the teacher!
		$teacher_obj->deleted = 1;
		$teacher_obj->save();

		$this->assertEquals(array(), \local_connect\sync::get_new_group_enrolments());
		$this->assertEquals(array($teacher), \local_connect\sync::get_deleted_group_enrolments());

		$teacher_obj->delete();

		$this->assertEquals(array(), \local_connect\sync::get_new_group_enrolments());
		$this->assertEquals(array(), \local_connect\sync::get_deleted_group_enrolments());
	}
}