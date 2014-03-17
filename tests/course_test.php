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

global $CFG;
require_once($CFG->dirroot . "/local/connect/classes/course.php");

/**
 * Tests new Kent course code
 */
class kent_course_tests extends local_connect\util\connect_testcase
{
    /**
     * Test we can create a course.
     */
    public function test_course_generator() {
        $this->resetAfterTest();
        $this->connect_cleanup();

        $this->generate_courses(20);

        $this->assertEquals(20, count(\local_connect\course::get_all()));

        $this->connect_cleanup();
    }

    /**
     * Test we can sync a course.
     */
    public function test_course_sync() {
        global $DB;

        $this->resetAfterTest();
        $this->connect_cleanup();

        $id = $this->generate_course();
        $course = \local_connect\course::get($id);

        // Creates.
        $this->assertFalse($course->is_in_moodle());
        $this->assertEquals("Creating Course: " . $course->id, $course->sync());
        $this->assertTrue($course->is_in_moodle());

        // Updates.
        $course->module_title = "TESTING NAME CHANGE";
        $this->assertEquals("Updating Course: " . $course->id, $course->sync());
        $this->assertTrue($course->is_in_moodle());
        $mcourse = $DB->get_record('course', array(
            "id" => $course->mid
        ), 'id,fullname');
        $this->assertEquals($course->fullname, $mcourse->fullname);

        $this->connect_cleanup();
    }

    /**
     * Test shortnames are always unique.
     */
    public function test_course_shortname_check() {
        $this->resetAfterTest();
        $this->connect_cleanup();

        $courseid = $this->generate_course();
        $course = \local_connect\course::get($courseid);
        $this->assertTrue($course->create_in_moodle());

        $courseid = $this->generate_course();
        $course2 = \local_connect\course::get($courseid);

        $this->assertTrue($course2->is_unique_shortname($course2->shortname));
        $course2->module_code = $course->module_code;
        $this->assertFalse($course2->is_unique_shortname($course2->shortname));

        $this->connect_cleanup();
    }

    /**
     * Test we can create a linked course.
     */
    public function test_linked_course() {
        $this->resetAfterTest();
        $this->connect_cleanup();

        // Create two courses.
        $course1 = \local_connect\course::get($this->generate_course());
        $course2 = \local_connect\course::get($this->generate_course());
        $this->assertEquals(2, count(\local_connect\course::get_all()));

        $this->assertFalse($course1->is_child());
        $this->assertFalse($course2->is_child());

        $lc = \local_connect\course::process_merge((object)array(
            'code' => "TST",
            'title' => "TEST MERGE",
            'synopsis' => "This is a test",
            'category' => 1,
            'link_courses' => array($course1->id, $course2->id)
        ));

        $this->assertTrue($lc->is_parent());
        $this->assertTrue($course1->is_child());
        $this->assertTrue($course2->is_child());

        $this->connect_cleanup();
    }

    /**
     * Test we can create a linked course and then unlink it.
     */
    public function test_unlink_course() {
        global $DB;

        $this->resetAfterTest();
        $this->connect_cleanup();

        // Create two courses.
        $course1 = \local_connect\course::get($this->generate_course());
        $course2 = \local_connect\course::get($this->generate_course());
        $this->assertEquals(2, count(\local_connect\course::get_all()));

        $lc = \local_connect\course::process_merge((object)array(
            'code' => "TST",
            'title' => "TEST MERGE",
            'synopsis' => "This is a test",
            'category' => 1,
            'link_courses' => array($course1->id, $course2->id)
        ));

        $this->assertTrue($lc->is_parent());
        $this->assertTrue($course1->is_child());
        $this->assertTrue($course2->is_child());

        // Unlink!
        $course1->unlink();

        $this->assertTrue($lc->is_parent());
        $this->assertFalse($course1->is_child());
        $this->assertTrue($course2->is_child());

        // TODO - test more stuff, enrolments etc

        $this->connect_cleanup();
    }

    /**
     * Test course counting methods.
     */
    public function test_course_counts() {
        global $DB;

        $this->resetAfterTest();
        $this->connect_cleanup();

        $course1 = \local_connect\course::get($this->generate_course());
        $this->generate_enrolments(100, $course1->id, 'student');
        $this->generate_enrolments(1, $course1->id, 'convenor');
        $this->generate_enrolments(2, $course1->id, 'teacher');
        $this->assertEquals(103, $course1->count_all());
        $this->assertEquals(100, $course1->count_students());
        $this->assertEquals(3, $course1->count_staff());

        $course2 = \local_connect\course::get($this->generate_course());
        $this->generate_enrolments(70, $course2->id, 'student');
        $this->generate_enrolments(1, $course2->id, 'convenor');
        $this->generate_enrolments(1, $course2->id, 'teacher');
        $this->assertEquals(72, $course2->count_all());
        $this->assertEquals(70, $course2->count_students());
        $this->assertEquals(2, $course2->count_staff());

        $course3 = \local_connect\course::get($this->generate_course());
        $this->generate_enrolments(700, $course3->id, 'student');
        $this->generate_enrolments(10, $course3->id, 'convenor');
        $this->generate_enrolments(10, $course3->id, 'teacher');

        // And all together.
        $this->assertEquals(100, $course1->count_students());
        $this->assertEquals(70, $course2->count_students());
        $this->assertEquals(3, $course1->count_staff());
        $this->assertEquals(2, $course2->count_staff());
        $this->assertEquals(103, $course1->count_all());
        $this->assertEquals(72, $course2->count_all());

        $this->connect_cleanup();
    }
}