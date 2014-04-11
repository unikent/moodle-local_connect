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
 * This synchronises Moodle with Connect
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require (dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'dry' => false
    )
);
$dry = $options['dry'];

// Roles
$c_roles = $DB->get_records('connect_role', array("mid" => 0));
foreach ($c_roles as $c_role) {
	$c_role_obj = \local_connect\role::get($c_role->id);
	$c_data = $c_role_obj->get_data_mapping();

	// Try to find a matching role.
	$m_role_id = $DB->get_field('role', 'id', array(
		'shortname' => $c_data['short']
	));
	if ($m_role_id !== false) {
		$c_role->mid = $m_role_id;
		if (!$dry) {
			$DB->update_record('connect_role', $c_role);
		}

		print "Mapped role {$c_role->name} to {$c_role->mid}.\n";
	}
}

// Users
$c_users = $DB->get_records('connect_user', array("mid" => 0));
foreach ($c_users as $c_user) {
	// Try to find a matching user.
	$m_user_id = $DB->get_field('user', 'id', array(
		'username' => $c_user->login
	));
	if ($m_user_id !== false) {
		$c_user->mid = $m_user_id;
		if (!$dry) {
			$DB->update_record('connect_user', $c_user);
		}

		print "Mapped user {$c_user->login} to {$c_user->mid}.\n";
	}
}

// Find all created Moodle courses that look like a connect course
$c_courses = $DB->get_records('connect_course', array("mid" => 0));
foreach ($c_courses as $c_course) {
	// Match a course on shortname
	$m_matches = $DB->get_records_sql("SELECT * FROM {course} WHERE shortname LIKE :shortname", array(
		"shortname" => "%" . $c_course->module_code . "%"
	));
	if (count($m_matches) == 1) {
		$m_course = array_pop($m_matches);
		$c_course->mid = $m_course->id;
		if (!$dry) {
			$DB->update_record('connect_course', $c_course);
		}

		print "Mapped course {$c_course->shortname} to {$c_course->mid}.\n";

		// Also, grab groups for this course and try some matching.
		$c_groups = $DB->get_records('connect_group', array(
			'mid' => 0,
			'courseid' => $c_course->id
		));
		foreach ($c_groups as $c_group) {
			// Try to match.
			$m_matches = $DB->get_records('groups', array('name' => $c_group->name));
			if (count($m_matches) == 1) {
				$m_group = array_pop($m_matches);
				$c_group->mid = $m_group->id;
				if (!$dry) {
					$DB->update_record('connect_group', $c_group);
				}

				print "Mapped group {$c_group->id} to {$c_group->mid}.\n";
			}
		}
	}
}
