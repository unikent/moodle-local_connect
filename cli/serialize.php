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
 * Serialize all data in connect.
 *
 * @package    local_connect
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$tables = array(
    'connect_campus',
    'connect_enrolments',
    'connect_group',
    'connect_group_enrolments',
    'connect_course',
    'connect_course_locks',
    'connect_course_exts',
    'connect_user',
    'connect_role',
    'connect_timetabling',
    'connect_type',
    'connect_room',
    'connect_weeks',
    'connect_course_handbook'
);

$data = array();
foreach ($tables as $table) {
    $data[$table] = $DB->get_records($table);
}

echo serialize($data);
