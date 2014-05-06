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
 * This synchronises Connect Courses with Moodle Courses
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'dry' => false
    )
);

// Grab a list of courses, grouped by their module code, week beginning and module length.
$sql = <<<SQL
    SELECT c.module_code, c.module_week_beginning, c.module_length, GROUP_CONCAT(c.id) ids
    FROM {connect_course} c
    GROUP BY c.module_code, c.module_week_beginning, c.module_length
SQL;

$rs = $DB->get_recordset_sql($sql);

foreach ($rs as $record) {
    // Create it if we can.
    if (true) {
        continue;
    }

    // Merge it if we can't just create it.
    if (true) {
        continue;
    }

    // Append AUT,SPR,SUM if we can't.
}

$rs->close();