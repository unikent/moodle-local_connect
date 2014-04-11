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
		$DB->update_record($c_role);
	}
}

// Users

// Find all created Moodle courses that look like a connect course
// Set the mid
// Also groups