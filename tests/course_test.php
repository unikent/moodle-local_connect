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
class kent_course_tests extends \local_connect\tests\connect_testcase
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

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        // Test locking.
        $this->assertTrue($course->is_locked());
        $mc = $DB->get_record('course', array(
            'id' => $course->mid
        ));
        $mc->fullname = 'TESTING NAME CHANGE';
        update_course($mc);
        $this->assertFalse($course->is_locked());

        $this->setUser(null);

        // Re-test.
        $id = $this->generate_course();
        $course = \local_connect\course::get($id);
        $course->create_in_moodle();
        $course->module_title = "TESTING NAME CHANGE 2";
        $course->save();

        $this->assertTrue($course->is_locked());
        $mc = $DB->get_record('course', array(
            'id' => $course->mid
        ));
        $mc->fullname = 'TESTING NAME CHANGE 2';
        update_course($mc);
        $this->assertTrue($course->is_locked());
    }

    /**
     * Test we can sync a merged course.
     */
    public function test_merged_course_sync() {
        global $DB;

        $this->resetAfterTest();

        $id = $this->generate_course();
        $course = \local_connect\course::get($id);
        $course->create_in_moodle();
        $this->assertEquals(\local_connect\data::STATUS_NONE, $course->sync());

        // Make sure we sync merged modules properly.
        $id2 = $this->generate_course();
        $DB->update_record('connect_course', array(
            'id' => $id2,
            'module_code' => $course->module_code,
            'module_version' => 2
        ));
        $course2 = \local_connect\course::get($id2);
        $course->add_child($course2);
        $course2->module_title = "TESTING SYNC";
        $course2->save();

        $this->assertTrue($course2->is_version_merged());
        $this->assertTrue($course2->is_locked());
        $this->assertTrue($course2->has_changed());

        $this->assertEquals($course2->id, $course->get_primary_version()->id);
        $this->assertEquals($course2->id, $course2->get_primary_version()->id);

        $course = \local_connect\course::get($id);
        $course2 = \local_connect\course::get($id2);

        $this->assertEquals($course->shortname, $course2->shortname);
        $this->assertEquals($course->fullname, $course2->fullname);
        $this->assertEquals($course->summary, $course2->summary);

        // Annndd if we sync?
        $this->assertEquals(\local_connect\data::STATUS_NONE, $course->sync());
        $this->assertEquals(\local_connect\data::STATUS_MODIFY, $course2->sync());
        $this->assertEquals(\local_connect\data::STATUS_NONE, $course2->sync());
        $this->assertEquals(\local_connect\data::STATUS_NONE, $course->sync());
    }

    /**
     * Test we can sync a course's enrolments.
     */
    public function test_course_enrol_sync() {
        global $DB;

        $this->resetAfterTest();
        $this->enable_enrol_plugin();
        $this->push_roles();

        $course = $this->generate_course();
        $this->generate_enrolments(30, $course, 'sds_student');
        $this->generate_enrolments(2, $course, 'sds_convenor');
        $this->generate_enrolments(1, $course, 'sds_teacher');
        $course = \local_connect\course::get($course);

        $course2 = $this->generate_course();
        $this->generate_enrolments(30, $course2, 'sds_student');
        $course2 = \local_connect\course::get($course2);

        $this->assertEquals(0, $DB->count_records('user_enrolments'));

        $this->assertTrue($course->create_in_moodle());
        $this->assertEquals(33, $DB->count_records('user_enrolments'));

        $course->add_child($course2);
        $this->assertEquals(63, $DB->count_records('user_enrolments'));

        $course2->unlink();
        $this->assertEquals(33, $DB->count_records('user_enrolments'));

        delete_course($course->mid);

        $this->assertEquals(0, $DB->count_records('user_enrolments'));
    }

    /**
     * Test shortnames are always unique.
     */
    public function test_course_shortname_check() {
        $this->resetAfterTest();

        $courseid = $this->generate_course();
        $course = \local_connect\course::get($courseid);
        $course->set_shortname_ext("TEST");
        $this->assertTrue($course->create_in_moodle());

        $courseid = $this->generate_course();
        $course2 = \local_connect\course::get($courseid);
        $course2->set_shortname_ext("TEST");

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
        $expected .= "... <a href='http://www.kent.ac.uk/courses/modulecatalogue/modules/{$course->shortname}'>more</a></div>&nbsp;<p style='margin-top:10px' class='module_summary_extra_info'>Canterbury, week 1-13</p>";

        for ($i = 0; $i < 53; $i++) {
            $synopsis .= ":";
        }

        $course->synopsis = $synopsis;

        $this->assertEquals($expected, $course->summary);
    }

    /**
     * Test we can create a linked course.
     */
    public function test_linked_courses() {
        global $DB;

        $this->resetAfterTest();

        // Create two courses.
        $course1 = \local_connect\course::get($this->generate_course());
        $course2 = \local_connect\course::get($this->generate_course());
        $this->assertEquals(2, count(\local_connect\course::get_all()));

        $result = \local_connect\course::process_merge(array($course1->id, $course2->id));

        $this->assertEquals(array(), $result);

        // Unlink!
        $this->assertTrue($course1->unlink());
    }

    /**
     * Test course counting methods.
     */
    public function test_course_counts() {
        global $DB;

        $this->resetAfterTest();

        $course1 = \local_connect\course::get($this->generate_course());
        $this->generate_enrolments(100, $course1->id, 'sds_student');
        $this->generate_enrolments(1, $course1->id, 'sds_convenor');
        $this->generate_enrolments(2, $course1->id, 'sds_teacher');
        $this->assertEquals(103, $course1->count_all());
        $this->assertEquals(100, $course1->count_students());
        $this->assertEquals(3, $course1->count_staff());

        $course2 = \local_connect\course::get($this->generate_course());
        $this->generate_enrolments(70, $course2->id, 'sds_student');
        $this->generate_enrolments(1, $course2->id, 'sds_convenor');
        $this->generate_enrolments(1, $course2->id, 'sds_teacher');
        $this->assertEquals(72, $course2->count_all());
        $this->assertEquals(70, $course2->count_students());
        $this->assertEquals(2, $course2->count_staff());

        $course3 = \local_connect\course::get($this->generate_course());
        $this->generate_enrolments(700, $course3->id, 'sds_student');
        $this->generate_enrolments(10, $course3->id, 'sds_convenor');
        $this->generate_enrolments(10, $course3->id, 'sds_teacher');

        // And all together.
        $this->assertEquals(100, $course1->count_students());
        $this->assertEquals(70, $course2->count_students());
        $this->assertEquals(3, $course1->count_staff());
        $this->assertEquals(2, $course2->count_staff());
        $this->assertEquals(103, $course1->count_all());
        $this->assertEquals(72, $course2->count_all());
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
        $this->assertTrue($course->create_in_moodle());
        $this->assertNotEquals('', $course->shortname_ext);

        $course->set_shortname_ext('TEST');
        $course->save();
        $this->assertEquals('TEST', $course->shortname_ext);

        $course = \local_connect\course::get($courseid);
        $this->assertEquals('TEST', $course->shortname_ext);
    }

    /**
     * If we move a course to the removed category,
     * kill its mid.
     */
    public function test_course_scheduled_mid() {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/course/lib.php");

        $this->resetAfterTest();

        $id = $this->generate_course();
        $course = \local_connect\course::get($id);

        // Creates.
        $this->assertFalse($course->is_in_moodle());
        $this->assertTrue($course->create_in_moodle());
        $this->assertTrue($course->is_in_moodle());

        // Delete the course.
        delete_course($course->mid);

        $course = \local_connect\course::get($id);
        $this->assertFalse($course->is_in_moodle());
    }

    /**
     * Make sure we lock courses properly.
     */
    public function test_course_lock() {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . "/course/lib.php");

        $this->resetAfterTest();

        // We can't be admin
        $user = $this->getDataGenerator()->create_user(array(
            'email' => 'user1@example.com',
            'username' => 'user1'
        ));
        $this->setUser($user);

        // Enable strict sync.
        set_config('strict_sync', true, 'local_connect');

        $id = $this->generate_course();
        $course = \local_connect\course::get($id);
        $course->create_in_moodle();

        $this->assertTrue($course->is_locked());

        $moodle = $DB->get_record('course', array(
            'id' => $course->mid
        ));
        $moodle->shortname = 'blahlol';
        update_course($moodle);

        $this->assertFalse($course->is_locked());

        $this->setUser(null);
    }
}