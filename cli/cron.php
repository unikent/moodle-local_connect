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

define('CLI_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

raise_memory_limit(MEMORY_HUGE);

\local_connect\util\cli::fix_mids();

\local_connect\util\migrate::all();
sleep($CFG->kent->cluster_sleep);

\local_connect\util\cli::enrolment_sync();
sleep($CFG->kent->cluster_sleep);

\local_connect\util\cli::group_sync();
sleep($CFG->kent->cluster_sleep);

\local_connect\util\cli::group_enrolment_sync();
sleep($CFG->kent->cluster_sleep);

\local_connect\util\cli::meta_sync();
sleep($CFG->kent->cluster_sleep);

// For 2014, also sync courses.
if ($CFG->kent->distribution === "2014") {
    \local_connect\util\cli::course_sync();
}