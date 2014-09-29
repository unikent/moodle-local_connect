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
class kent_sync_tests extends \local_connect\tests\connect_testcase
{
    /**
     * Test group creates.
     */
    public function test_group_creates() {
        global $CFG, $DB;

        $this->resetAfterTest();

        $this->assertEquals(array(), \local_connect\util\sync::get_new_groups());

        $course = $this->generate_course();
        $course_obj = \local_connect\course::get($course);
        $course_obj->create_in_moodle();

        $this->generate_groups(20, $course);

        $this->assertEquals(20, count(\local_connect\util\sync::get_new_groups()));

        foreach (\local_connect\group::get_all() as $group) {
            $group->create_in_moodle();
        }

        $this->assertEquals(array(), \local_connect\util\sync::get_new_groups());
    }

    /**
     * Test group enrolment creates.
     */
    public function test_group_enrolment_creates() {
        global $CFG, $DB;

        $this->resetAfterTest();

        $this->assertEquals(array(), \local_connect\util\sync::get_new_group_enrolments());

        // Grab us a course.
        $course = $this->generate_course();
        $course_obj = \local_connect\course::get($course);
        $course_obj->create_in_moodle();

        // Create a group.
        $group = $this->generate_group($course);
        $group_obj = \local_connect\group::get($group);
        $group_obj->create_in_moodle();

        $this->assertEquals(array(), \local_connect\util\sync::get_new_group_enrolments());

        // Generate a few enrolments.
        $this->generate_group_enrolments(30, $group, 'student');
        $this->generate_group_enrolments(2, $group, 'teacher');

        $this->assertEquals(32, count(\local_connect\util\sync::get_new_group_enrolments()));

        foreach (\local_connect\group_enrolment::get_all() as $enrolment) {
            $enrolment->create_in_moodle();
        }

        $this->assertEquals(array(), \local_connect\util\sync::get_new_group_enrolments());

        // Also check we dont register deleted ones..
        $teacher = $this->generate_group_enrolment($group, 'teacher');

        $this->assertEquals(array($teacher), \local_connect\util\sync::get_new_group_enrolments());

        $DB->update_record('connect_group_enrolments', array(
            "id" => $teacher,
            "deleted" => 1
        ));

        $this->assertEquals(array(), \local_connect\util\sync::get_new_group_enrolments());
    }

    /**
     * Test group enrolment deletes.
     */
    public function test_group_enrolment_deletes() {
        global $CFG, $DB;

        $this->resetAfterTest();

        $this->assertEquals(array(), \local_connect\util\sync::get_new_group_enrolments());
        $this->assertEquals(array(), \local_connect\util\sync::get_deleted_group_enrolments());

        // Grab us a course.
        $course = $this->generate_course();
        $course_obj = \local_connect\course::get($course);
        $course_obj->create_in_moodle();

        // Create a group.
        $group = $this->generate_group($course);
        $group_obj = \local_connect\group::get($group);
        $group_obj->create_in_moodle();

        $this->assertEquals(array(), \local_connect\util\sync::get_new_group_enrolments());
        $this->assertEquals(array(), \local_connect\util\sync::get_deleted_group_enrolments());

        // Generate a few enrolments.
        $this->generate_group_enrolments(30, $group, 'student');
        $teacher = $this->generate_group_enrolment($group, 'teacher');
        $teacher_obj = \local_connect\group_enrolment::get($teacher);

        $this->assertEquals(31, count(\local_connect\util\sync::get_new_group_enrolments()));

        foreach (\local_connect\group_enrolment::get_all() as $enrolment) {
            $enrolment->create_in_moodle();
        }

        $this->assertEquals(array(), \local_connect\util\sync::get_new_group_enrolments());
        $this->assertEquals(array(), \local_connect\util\sync::get_deleted_group_enrolments());

        // Delete the teacher!
        $teacher_obj->deleted = 1;
        $teacher_obj->save();

        $this->assertEquals(array(), \local_connect\util\sync::get_new_group_enrolments());
        $this->assertEquals(array($teacher), \local_connect\util\sync::get_deleted_group_enrolments());

        $teacher_obj->delete();

        $this->assertEquals(array(), \local_connect\util\sync::get_new_group_enrolments());
        $this->assertEquals(array(), \local_connect\util\sync::get_deleted_group_enrolments());
    }
}