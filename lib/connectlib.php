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
 * Functions and classes used for Connect
 *
 * @package    local
 * @subpackage connect
 * @copyright  2013 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function connect_db() {
	global $CFG;
	
	static $db;
	if (!isset($db)) {
		$db = new PDO($CFG->connect->db['dsn'] . ";dbname=" . $CFG->connect->db['name'], $CFG->connect->db['user'], $CFG->connect->db['password']);
	}
	return $db;
}

/**
 * Returns a list of a user's courses
 */
function connect_get_user_courses($username) {
	$data = array();
	$pdo = connect_db();

	// Select all our courses
	$sql = "SELECT e.moodle_id enrolmentid, c.moodle_id courseid FROM `enrollments` e
				LEFT JOIN `courses` c
				ON c.module_delivery_key = e.module_delivery_key
			WHERE e.login=:username";
	$stmt = $pdo->prepare($sql);
	$stmt->execute(array("username" => $username));

	return $stmt->fetchAll();
}

/**
 * Returns a list of this user's courses
 */
function connect_get_my_courses() {
	return connect_get_user_courses($USER->username);
}