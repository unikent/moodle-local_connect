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
        $this->connect_cleanup();

        $this->generate_courses(20);

        $this->assertEquals(20, count(\local_connect\course::get_courses()));

        $this->connect_cleanup();
    }

    /**
     * Test we can create a linked course.
     */
    public function test_linked_course() {
        $this->resetAfterTest();
        $this->connect_cleanup();

        // Create two courses.
        $this->generate_courses(2);

        $courses = \local_connect\course::get_courses(array(), true);
        $this->assertEquals(2, count($courses));

        $link_course = array(
            'module_code' => "TST",
            'module_title' => "TEST MERGE",
            'primary_child' => reset($courses),
            'synopsis' => "This is a test",
            'category_id' => 1,
            'state' => \local_connect\course::$states['scheduled'],
            'moodle_id' => null
        );

        $this->assertEquals(array(), \local_connect\course::merge($link_course, $courses));

        $courses = \local_connect\course::get_courses();
        $this->assertEquals(3, count($courses));

        $this->connect_cleanup();
    }
}