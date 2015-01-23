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
class kent_group_enrolment_tests extends \local_connect\tests\connect_testcase
{
	/**
	 * Make sure we can grab a valid list of enrolments.
	 */
	public function test_group_enrolment_list() {
		global $CFG;

		$this->resetAfterTest();

		// Grab some delivery keys.
		$course = $this->generate_course();
		$course2 = $this->generate_course();

		// Create some groups.
		$this->generate_groups(20, $course);
		$this->generate_groups(10, $course2);
		$group = $this->generate_group($course);
		$group2 = $this->generate_group($course2);

		// Test the global count.
		$enrolments = \local_connect\group_enrolment::get_all();
		$this->assertEquals(0, count($enrolments));

		// Generate a few enrolments.
		$this->generate_group_enrolments(30, $group, 'sds_student');
		$this->generate_group_enrolments(2, $group, 'sds_teacher');
		$this->generate_group_enrolments(20, $group2, 'sds_student');
		$this->generate_group_enrolments(1, $group2, 'sds_teacher');

		// Test the global count.
		$enrolments = \local_connect\group_enrolment::get_all();
		$this->assertEquals(53, count($enrolments));

		// Test the group counter.
		$obj = \local_connect\group::get($group);
		$enrolments = \local_connect\group_enrolment::get_by("groupid", $obj->id, true);
		$this->assertEquals(32, count($enrolments));
		$obj = \local_connect\group::get($group2);
		$enrolments = \local_connect\group_enrolment::get_by("groupid", $obj->id, true);
		$this->assertEquals(21, count($enrolments));
	}

	/**
	 * Make sure we can create a valid group enrolment in Moodle.
	 */
	public function test_group_enrolment_create() {
		global $CFG;

		$this->resetAfterTest();

		$course = $this->generate_course();

		// Create the course in Moodle
		{
			$obj = \local_connect\course::get($course);
			$obj->create_in_moodle();
		}

		$group = $this->generate_group($course);

		// Create the group in Moodle
		{
			$obj = \local_connect\group::get($group);
			$obj->create_in_moodle();
		}

		$ge = $this->generate_group_enrolment($group, 'sds_teacher');

		$obj = \local_connect\group_enrolment::get($ge);
		$this->assertFalse($obj->is_in_moodle());
		$this->assertTrue($obj->create_in_moodle());
		$this->assertTrue($obj->is_in_moodle());

	}

	/**
	 * Make sure we can delete a group enrolment in Moodle.
	 */
	public function test_group_enrolment_delete() {
		$this->resetAfterTest();

		$course = $this->generate_course();

		// Create the course in Moodle
		{
			$obj = \local_connect\course::get($course);
			$obj->create_in_moodle();
		}

		$group = $this->generate_group($course);

		// Create the group in Moodle
		{
			$obj = \local_connect\group::get($group);
			$obj->create_in_moodle();
		}

		$ge = $this->generate_group_enrolment($group, 'sds_teacher');

		$obj = \local_connect\group_enrolment::get($ge);
		$this->assertFalse($obj->is_in_moodle());
		$this->assertTrue($obj->create_in_moodle());
		$this->assertTrue($obj->is_in_moodle());
		$obj->delete();
		$this->assertFalse($obj->is_in_moodle());

	}

	/**
	 * Make sure we can get course enrolments properly.
	 */
	public function test_group_enrolment_get_for_course() {
		$this->resetAfterTest();

		$course = $this->generate_course();
		$courseobj = \local_connect\course::get($course);

		$group = $this->generate_group($course);
		$this->generate_group_enrolments(40, $group, 'sds_student');

		$this->assertEquals(40, count(\local_connect\group_enrolment::get_for_course($courseobj)));

		$group = $this->generate_group($course);
		$this->generate_group_enrolments(20, $group, 'sds_student');

		$this->assertEquals(60, count(\local_connect\group_enrolment::get_for_course($courseobj)));

		$course2 = $this->generate_course();
		$course2obj = \local_connect\course::get($course2);

		$group = $this->generate_group($course2);

		$this->generate_group_enrolments(20, $group, 'sds_student');

		$this->assertEquals(60, count(\local_connect\group_enrolment::get_for_course($courseobj)));
		$this->assertEquals(20, count(\local_connect\group_enrolment::get_for_course($course2obj)));

	}

	/**
	 * Make sure we can sync properly.
	 */
	public function test_group_enrolment_sync() {
		$this->resetAfterTest();

		$course = $this->generate_course();

		// Create the course in Moodle
		{
			$obj = \local_connect\course::get($course);
			$obj->create_in_moodle();
		}

		$group = $this->generate_group($course);

		// Create the group in Moodle
		{
			$obj = \local_connect\group::get($group);
			$obj->create_in_moodle();
		}

		$ge = $this->generate_group_enrolment($group, 'sds_teacher');

		$obj = \local_connect\group_enrolment::get($ge);
		$this->assertFalse($obj->is_in_moodle());
		$this->assertEquals(\local_connect\data::STATUS_CREATE, $obj->sync());
		$this->assertTrue($obj->is_in_moodle());
	}

    /**
     * Test group_enrolment_created event
     */
    public function test_group_enrolment_created_event() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $params = array(
            'objectid' => 1,
            'relateduserid' => 2,
            'courseid' => $course->id,
            'context' => \context_course::instance($course->id),
            'other' => array(
                'groupid' => 2
            )
        );
        $event = \local_connect\event\group_enrolment_created::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\local_connect\event\group_enrolment_created', $event);
    }
}