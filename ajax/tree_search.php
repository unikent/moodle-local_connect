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
 * Which tree nodes should be expanded based on the current search terms?
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');

global $PAGE, $OUTPUT, $USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/ajax/tree_search.php');

require_capability("local/helpdesk:access", context_system::instance());

$query = required_param('str', PARAM_RAW_TRIMMED);

$out = array();
$out[] = "courses";
$out[] = "users";

// This could be really difficult, but we basically want to split this thing up..
// It may seem odd but it is the most elegant way of solving the problem as JS cleans up for us.
for ($i = 0; $i <= strlen($query); $i++) {
	$substr = substr($query, 0, $i);
	$out[] = "c_" . $substr;
	$out[] = "u_" . $substr;
	$out[] = "u_convenor_" . $substr;
	$out[] = "u_teacher_" . $substr;
	$out[] = "u_student_" . $substr;
}

echo $OUTPUT->header();
echo json_encode($out);
