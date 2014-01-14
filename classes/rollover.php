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
 * Connect rollover container
 */
class rollover {

	/**
	 * Returns a list of sources for rollover
	 * 
	 * @return array
	 */
	public static function get_course_list($dist = '', $shortname = '') {
		global $CFG, $SHAREDB;

		$sql = 'SELECT * FROM {course_list} WHERE moodle_env = :current_env';
		$sql .= empty($dist) ? ' AND moodle_dist != :current_dist' : ' AND moodle_dist = :current_dist';

		$params = array(
            'current_env' => $CFG->kent->environment,
            'current_dist' => empty($dist) ? $CFG->kent->distribution : $dist
        );

		if (!empty($shortname)) {
			$shortname = "%" . $shortname . "%";
			$sql .= ' AND ' . $SHAREDB->sql_like('shortname', ':shortname', false);
			$params['shortname'] = $shortname;
		}

        return $SHAREDB->get_records_sql($sql, $params);
	}

	/**
	 * Returns a list of sources for rollover
	 * 
	 * @return array
	 */
	public static function get_source_list($dist = '') {
		return static::get_course_list('');
	}

	/**
	 * Returns a list of targets for rollover
	 * 
	 * @return array
	 */
	public static function get_target_list() {
		global $CFG;
		return static::get_course_list($CFG->kent->distribution);
	}

}