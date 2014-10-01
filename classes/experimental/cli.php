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
 * The new Sink::run.
 * 
 * @todo  Current waiting on changes to Custard config
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

cli_error("This script is not yet supported.");

raise_memory_limit(MEMORY_HUGE);

echo "Beginning Connect Sync...\n";

/*
 * --------------------------------------------------------
 * Create missing groups
 * --------------------------------------------------------
 */

$creates = \local_connect\experimental\sync::get_new_groups();
$count = count($creates);
echo "   Creating ($count) groups...\n";

foreach ($creates as $create) {
    $obj = \local_connect\group::get($create);
    echo "      -> {$obj->id} ({$obj->name})\n";

    if (!$options['dry']) {
        $obj->create_in_moodle();
    }
}

unset($creates);

/*
 * --------------------------------------------------------
 * Group enrolments!
 * --------------------------------------------------------
 */

// First, deletes.
$deletes = \local_connect\experimental\sync::get_deleted_group_enrolments();
$count = count($deletes);
echo "   Deleting ($count) group enrolments...\n";

foreach ($deletes as $delete) {
    $obj = \local_connect\group_enrolment::get($delete);
    echo "      -> {$obj->userid} in group {$obj->groupid}\n";

    if (!$options['dry']) {
        $obj->delete();
    }
}

unset($deletes);

// Then, creates.
$creates = \local_connect\experimental\sync::get_new_group_enrolments();
$count = count($creates);
echo "   Creating ($count) group enrolments...\n";

foreach ($creates as $create) {
    $obj = \local_connect\group_enrolment::get($create);
    echo "      -> {$obj->userid} in group {$obj->groupid}\n";

    if (!$options['dry']) {
        $obj->create_in_moodle();
    }
}

unset($creates);