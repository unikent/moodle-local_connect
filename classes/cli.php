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
	public static function course_sync($dry_run = false, $moodle_id = null) {
		$courses = array();

		// What are we syncing, one or all?
		if (isset($moodle_id)) {
			mtrace("  Synchronizing course: '{$moodle_id}'...\n");

			// Get the connect version of the course.
			$courses = course::get_by_moodle_id($moodle_id);

			// Validate the course.
			if (empty($courses)) {
				mtrace("  Invalid course: '{$moodle_id}'");
				return false;
			}

			// Are we in Moodle?
			foreach ($courses as $connect_course) {
				if (!$connect_course->is_in_moodle()) {
					mtrace("  Connect Course '{$connect_course->id}' does not exist in Moodle (but it has an mid set).");
					return false;
				}
			}
		} else {
			mtrace("  Synchronizing courses...\n");
			$courses = course::get_all();
		}

		foreach ($courses as $course) {
			try {
				$result = $course->sync($dry_run);
		    	if ($result !== null) {
		    		mtrace("    " . $result);
		    	}
			} catch (Excepton $e) {
				$msg = $e->getMessage();
				mtrace("    Error: $msg\n");
			}
		}

		mtrace("  done.\n");
	}

	/**
	 * Run the enrolment sync cron
	 */
	public static function enrolment_sync($dry_run = false, $moodle_id = null) {
		global $CFG;

		$enrolments = array();

		if (isset($moodle_id)) {
			mtrace("  Synchronizing enrolments for course: '{$moodle_id}'...\n");

			// Get the connect version of the course.
			$courses = course::get_by_moodle_id($moodle_id);

			// Validate the course.
			if (empty($courses)) {
				mtrace("  Invalid course ID: $moodle_id");
				return false;
			}

			// We have a valid course(s)!
			foreach ($courses as $connect_course) {
				if ($connect_course->is_in_moodle()) {
					$enrolments = array_merge($connect_course->enrolments, $enrolments);
				}
			}
		} else {
			mtrace("  Synchronizing enrolments...\n");
			$enrolments = enrolment::get_all();
		}

		foreach ($enrolments as $enrolment) {
	    	$result = $enrolment->sync($dry_run);
	    	if ($result !== null) {
	    		mtrace("    " . $result);
	    	}
		}

		mtrace("  done.\n");
	}

	/**
	 * Run the group sync cron
	 */
	public static function group_sync($dry_run = false, $moodle_id = null) {
		global $CFG;

		$groups = array();

		if (isset($moodle_id)) {
			mtrace("  Synchronizing groups for course: '{$moodle_id}'...\n");

			// Get the connect version of the course.
			$courses = course::get_by_moodle_id($moodle_id);

			// Validate the course.
			if (empty($courses)) {
				mtrace("  Invalid course ID: $moodle_id");
				return false;
			}

			// We have a valid course(s)!
			foreach ($courses as $connect_course) {
				if ($connect_course->is_in_moodle()) {
					$groups = array_merge($connect_course->groups, $groups);
				}
			}
		} else {
			mtrace("  Synchronizing groups...\n");
			$groups = group::get_all();
		}

		foreach ($groups as $group) {
	    	$result = $group->sync($dry_run);
	    	if ($result !== null) {
	    		mtrace("    " . $result);
	    	}
		}

		mtrace("  done.\n");
	}

	/**
	 * Run the group enrolment sync cron
	 */
	public static function group_enrolment_sync($dry_run = false, $moodle_id = null) {
		global $CFG;

		$group_enrolments = array();

		if (isset($moodle_id)) {
			mtrace("  Synchronizing group enrolments for course: '{$moodle_id}'...\n");

			// Get the connect version of the course.
			$courses = course::get_by_moodle_id($moodle_id);

			// Validate the course.
			if (empty($courses)) {
				mtrace("  Invalid course ID: $moodle_id");
				return false;
			}

			// We have a valid course!
			foreach ($courses as $connect_course) {
				if ($connect_course->is_in_moodle()) {
					$group_enrolments = array_merge($connect_course->group_enrolments, $group_enrolments);
				}
			}
		} else {
			mtrace("  Synchronizing group enrolments...\n");
			$group_enrolments = group_enrolment::get_all();
		}

		foreach ($group_enrolments as $group_enrolment) {
		    $result = $group_enrolment->sync($dry_run);
	    	if ($result !== null) {
	    		mtrace("    " . $result);
	    	}
		}

		mtrace("  done.\n");
	}

}
