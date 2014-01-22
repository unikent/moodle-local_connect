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

/**
 * Local stuff for Moodle Connect
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect;

defined('MOODLE_INTERNAL') || die();

/**
 * Connect CLI helpers
 */
class cli {

	/**
	 * Run the course sync cron
	 */
	public static function course_sync() {
		mtrace("  Synchronizing courses...\n");
		$courses = \local_connect\course::get_courses(array(), true);
		foreach ($courses as $course) {
			try {

				if (!$course->is_created() && $course->has_unique_shortname()) {
					mtrace("    Creating $course...\n");
					$course->create_moodle();
					continue;
				}

				if ($course->has_changed()) {
					mtrace("    Updating $course...\n");
					$course->update_moodle();
					continue;
				}

			} catch (Excepton $e) {
				$msg = $e->getMessage();
				mtrace("    Error: $msg\n");
			}
		}
		mtrace("  done.\n");
	}

	/**
	 * Run the group sync cron
	 */
	public static function group_sync() {
		mtrace("  Synchronizing groups...\n");

		$groups = \local_connect\group::get_all($CFG->connect->session_code);
		foreach ($groups as $group) {
		    if (!$group->is_in_moodle()) {
		        if ($group->create_in_moodle()) {
		        	mtrace("    Created group '{$group->id}'!\n");
		        } else {
		        	mtrace("    Failed group '{$group->id}'!\n");
		        }
		    }
		}

		mtrace("  done.\n");
	}

	/**
	 * Run the group enrolment sync cron
	 */
	public static function group_enrolment_sync() {
		mtrace("  Synchronizing group enrolments...\n");

		$data = new \stdClass();
		$data->id = 126253;

		$groups = \local_connect\group_enrolment::get_for_group($data);foreach ($groups as $group) {
		if (!$group->is_in_moodle()) {
			$group->create_in_moodle();
		}
	}

		/*$groups = \local_connect\group_enrolment::get_all($CFG->connect->session_code);
		foreach ($groups as $group) {
		    if (!$group->is_in_moodle()) {
		        if ($group->create_in_moodle()) {
		        	mtrace("    Created group '{$group->id}'!\n");
		        } else {
		        	mtrace("    Failed group '{$group->id}'!\n");
		        }
		    }
		}*/

		mtrace("  done.\n");
	}

}