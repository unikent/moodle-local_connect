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
        'clean' => false,
        'update' => false,
        'new' => false,
        'all' => false,
        'sanitize-timetabling' => false,
        'map-roles' => false,
        'map-users' => false
    )
);

raise_memory_limit(MEMORY_HUGE);

if ($options['clean']) {
    \local_connect\util\migrate::empty_all();
}

if ($options['update']) {
    \local_connect\util\migrate::all_updated();
}

if ($options['new']) {
    \local_connect\util\migrate::all_create();
}

if ($options['all']) {
    \local_connect\util\migrate::all();
}

if ($options['map-roles']) {
    \local_connect\util\migrate::map_roles();
}

if ($options['map-users']) {
    \local_connect\util\migrate::map_users();
}

if ($options['sanitize-timetabling']) {
    \local_connect\util\migrate::sanitize_timetabling();
}