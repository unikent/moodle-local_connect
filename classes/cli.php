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
 * @package    core_connect
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
		$courses = \local_connect\course::get_courses(array(), true);
		foreach ($courses as $course) {
			try {

				if (!$course->is_created() && $course->has_unique_shortname()) {
					print "Creating $course...\n";
					$course->create_moodle();
					continue;
				}

				if ($course->has_changed()) {
					print "Updating $course...\n";
					$course->update_moodle();
					continue;
				}

			} catch (Excepton $e) {
				$msg = $e->getMessage();
				print "Error: $msg\n";
			}
		}
	}

}