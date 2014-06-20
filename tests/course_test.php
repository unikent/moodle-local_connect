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
 * Tests new Kent course code
 */
class kent_course_tests extends local_connect\util\connect_testcase
{
    /**
     * Test we can create a course.
     */
    public function test_course_generator() {
        $this->resetAfterTest();

        $this->generate_courses(20);
        $this->assertEquals(20, count(\local_connect\course::get_all()));
    }

    /**
     * Test we can sync a course.
     */
    public function test_course_sync() {
        global $DB;

        $this->resetAfterTest();

        $id = $this->generate_course();
        $course = \local_connect\course::get($id);

        // Creates.
        $this->assertFalse($course->is_in_moodle());
        $this->assertTrue($course->create_in_moodle());
        $this->assertTrue($course->is_in_moodle());

        // Updates.
        $course->module_title = "TESTING NAME CHANGE";

        $this->assertEquals(\local_connect\data::STATUS_NONE, $course->sync());
        set_config('strict_sync', true, 'local_connect');
        $this->assertEquals(\local_connect\data::STATUS_MODIFY, $course->sync());

        $this->assertTrue($course->is_in_moodle());
        $mcourse = $DB->get_record('course', array(
            "id" => $course->mid
        ), 'id,fullname');
        $this->assertEquals($course->fullname, $mcourse->fullname);
    }

    /**
     * Test shortnames are always unique.
     */
    public function test_course_shortname_check() {
        $this->resetAfterTest();

        $courseid = $this->generate_course();
        $course = \local_connect\course::get($courseid);
        $this->assertTrue($course->create_in_moodle());

        $courseid = $this->generate_course();
        $course2 = \local_connect\course::get($courseid);

        $this->assertTrue($course2->is_unique_shortname($course2->shortname));
        $course2->module_code = $course->module_code;
        $this->assertFalse($course2->is_unique_shortname($course2->shortname));
    }

    /**
     * Test summary generator
     */
    public function test_course_summary_check() {
        $this->resetAfterTest();

        $courseid = $this->generate_course();
        $course = \local_connect\course::get($courseid);
        $this->assertEquals('A test course', $course->synopsis);

        $expected = "<div class=\"synopsistext\">A test course</div>&nbsp;";
        $expected .= "<p style='margin-top:10px' class='module_summary_extra_info'>Canterbury, week 1-13</p>";
        $this->assertEquals($expected, $course->summary);

        // Also test we properly shorten them!

        $synopsis = "";
        for ($i = 0; $i < 247; $i++) {
            $synopsis .= ":";
        }

        $expected = "<div class=\"synopsistext\">";
        $expected .= $synopsis;
        $expected .= "... more</div>&nbsp;<p style='margin-top:10px' class='module_summary_extra_info'>Canterbury, week 1-13</p>";

        for ($i = 0; $i < 53; $i++) {
            $synopsis .= ":";
        }

        $course->synopsis = $synopsis;

        $this->assertEquals($expected, $course->summary);
    }

    /**
     * Test we can create a linked course.
     */
    public function test_linked_course() {
        $this->resetAfterTest();

        // Create two courses.
        $course1 = \local_connect\course::get($this->generate_course());
        $course2 = \local_connect\course::get($this->generate_course());
        $this->assertEquals(2, count(\local_connect\course::get_all()));

        $result = \local_connect\course::process_merge((object)array(
            'code' => "TST",
            'title' => "TEST MERGE",
            'synopsis' => "This is a test",
            'category' => 1,
            'link_courses' => array($course1->id, $course2->id)
        ));

        $this->assertEquals(array(), $result);
    }

    /**
     * Test we can create a linked course and then unlink it.
     */
    public function test_unlink_course() {
        global $DB;

        $this->resetAfterTest();

        // Create two courses.
        $course1 = \local_connect\course::get($this->generate_course());
        $course2 = \local_connect\course::get($this->generate_course());
        $this->assertEquals(2, count(\local_connect\course::get_all()));

        $result = \local_connect\course::process_merge((object)array(
            'code' => "TST",
            'title' => "TEST MERGE",
            'synopsis' => "This is a test",
            'category' => 1,
            'link_courses' => array($course1->id, $course2->id)
        ));

        $this->assertEquals(array(), $result);

        // Unlink!
        $this->assertTrue($course1->unlink());

        // TODO - test more stuff, enrolments etc.
    }

    /**
     * Test course counting methods.
     */
    public function test_course_counts() {
        global $DB;

        $this->resetAfterTest();

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
    }

    /**
     * Test campus_name.
     */
    public function test_campus_name() {
        $this->resetAfterTest();

        $course = \local_connect\course::get($this->generate_course());
        $this->assertTrue(in_array($course->campus_name, array("Canterbury", "Medway")), $course->campus_name);
    }

    /**
     * Test course_created event
     */
    public function test_course_created_event() {
        $this->resetAfterTest();

        $course = \local_connect\course::get($this->generate_course());
        $this->assertTrue($course->create_in_moodle());

        $params = array(
            'objectid' => $course->id,
            'courseid' => $course->mid,
            'context' => \context_course::instance($course->mid)
        );
        $event = \local_connect\event\course_created::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\local_connect\event\course_created', $event);
    }

    /**
     * Test course shortname methods
     */
    public function test_course_shortname_ext() {
        $this->resetAfterTest();

        $courseid = $this->generate_course();
        $course = \local_connect\course::get($courseid);

        $this->assertEquals('', $course->shortname_ext);
        $course->set_shortname_ext('TEST');
        $course->save();
        $this->assertEquals('TEST', $course->shortname_ext);

        $course = \local_connect\course::get($courseid);
        $this->assertEquals('TEST', $course->shortname_ext);
    }
}