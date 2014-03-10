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
	public static function course_sync($dry_run = false) {
		mtrace("  Synchronizing courses...\n");
		$courses = course::get_courses(array(), true);
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
	public static function enrolment_sync($dry_run = false, $course_id = null) {
		global $CFG;

		mtrace("  Synchronizing enrolments...\n");

		$enrolments = enrolment::get_all($CFG->connect->session_code);
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
	public static function group_sync($dry_run = false) {
		global $CFG;

		mtrace("  Synchronizing groups...\n");

		$groups = group::get_all($CFG->connect->session_code);
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
	public static function group_enrolment_sync($dry_run = false, $course_id = null) {
		global $CFG;

		mtrace("  Synchronizing group enrolments...\n");

		$group_enrolments = group_enrolment::get_all($CFG->connect->session_code);
		foreach ($group_enrolments as $group_enrolment) {
		    $result = $group_enrolment->sync($dry_run);
	    	if ($result !== null) {
	    		mtrace("    " . $result);
	    	}
		}

		mtrace("  done.\n");
	}

}