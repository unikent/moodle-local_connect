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
 * Connect Cron
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

require_once($CFG->libdir . "/clilib.php");

// Only enable this for 2014.
if (\local_connect\utils::is_enabled() && \local_connect\utils::enable_new_features() && \local_connect\utils::enable_cron()) {
	mtrace('');

	// Sync courses
	\local_connect\cli::course_sync();
	mtrace('');
	
	// Sync groups
	\local_connect\cli::group_sync();
	mtrace('');
	
	// Sync group enrolments
	\local_connect\cli::group_enrolment_sync();
	mtrace('');
}
