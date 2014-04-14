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
 * Prints out JSON for the sublist of a given browser tree-node.
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');

global $PAGE, $OUTPUT, $USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/ajax/tree_data.php');

if (!has_capability('moodle/site:config', context_system::instance())) {
    throw new moodleexception('accessdenied', 'admin');
}

$node = optional_param('id', '#', PARAM_RAW_TRIMMED);
$node = empty($node) ? '#' : $node;

$out = array();

if ($node == '#') {
	$out[] = array(
		"id" => "courses",
		"parent" => "#",
		"text" => "Courses",
		"children" => true
	);
	$out[] = array(
		"id" => "users",
		"parent" => "#",
		"text" => "Users",
		"children" => true
	);
}

if ($node == 'users') {
	$out[] = array(
		"id" => "teachers",
		"parent" => "users",
		"text" => "Teachers",
		"children" => true
	);
	$out[] = array(
		"id" => "convenors",
		"parent" => "users",
		"text" => "Convenors",
		"children" => true
	);
	$out[] = array(
		"id" => "students",
		"parent" => "users",
		"text" => "Students",
		"children" => true
	);
}

if ($node == 'courses') {
	// Grab the courses.
}

if ($node == 'teachers' || $node == 'convenors' || $node == 'students') {
	// Basically the alphabet...
	$users = $DB->get_records_sql('SELECT LEFT(login, 1) as ch FROM {connect_user} GROUP BY ch');
	foreach ($users as $user) {
		$out[] = array(
			"id" => "user_" . $user->ch,
			"parent" => $node,
			"text" => $user->ch . "...",
			"icon" => "user",
			"children" => true
		);
	}
}

if (strpos($node, "user_") !== false) {
	$username = substr($node, 5);

	if (strlen($username) === 1) {
		$users = $DB->get_records_sql('SELECT LEFT(login, 2) as ch FROM {connect_user} WHERE login LIKE :username GROUP BY ch', array(
			"username" => "{$username}%"
		));
		foreach ($users as $user) {
			$out[] = array(
				"id" => "user_" . $user->ch,
				"parent" => $node,
				"text" => $user->ch . "...",
				"icon" => "user",
				"children" => true
			);
		}
	} else {
		$users = $DB->get_records_sql('SELECT id, login FROM {connect_user} WHERE login LIKE :username', array(
			"username" => "{$username}%"
		));
		foreach ($users as $user) {
			$out[] = array(
				"id" => $user->login,
				"parent" => $node,
				"text" => $user->login,
				"icon" => "user"
			);
		}
	}
}

echo $OUTPUT->header();
echo json_encode($out);
