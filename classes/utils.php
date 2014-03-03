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
 * Connect utils
 */
class utils {

	/**
	 * Is connect configured properly?
	 */
	public static function is_enabled() {
		global $CFG;
		return isset($CFG->local_connect_enable) && $CFG->local_connect_enable;
	}

	/**
	 * Enable the fancy new connect features?
	 */
	public static function enable_new_features() {
		global $CFG;
		return static::is_enabled() && isset($CFG->local_connect_enable_new_features) && $CFG->local_connect_enable_new_features;
	}

	/**
	 * Enable the fancy new connect observers?
	 */
	public static function enable_new_observers() {
		global $CFG;
		return static::is_enabled() && isset($CFG->local_connect_enable_observers) && $CFG->local_connect_enable_observers;
	}

	/**
	 * Enable the SHAREDB?
	 */
	public static function enable_sharedb() {
		global $CFG;
		return static::is_enabled() && isset($CFG->local_connect_enable_sharedb) && $CFG->local_connect_enable_sharedb;
	}

	/**
	 * Enable the cron?
	 */
	public static function enable_cron() {
		global $CFG;
		return static::is_enabled() && isset($CFG->local_connect_enable_cron) && $CFG->local_connect_enable_cron;
	}

	/**
	 * Enable rollover?
	 */
	public static function enable_rollover() {
		global $CFG;
		return static::is_enabled() && isset($CFG->local_connect_enable_rollover) && $CFG->local_connect_enable_rollover;
	}
}