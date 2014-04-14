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

require_capability("local/helpdesk:access", context_system::instance());

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
    $roles = $DB->get_records('connect_role');
    foreach ($roles as $role) {
		$out[] = array(
			"id" => $role->name . "s",
			"parent" => "users",
			"text" => ucfirst($role->name . "s"),
			"children" => true
		);
    }
}

/**
 * Returns a set of data for tree view display
 */
function grab_set($file, $node, $raw_node_data, $table, $column, $prefix, $left = 1, $data = null) {
	global $DB;

	if (empty($data)) {
		$data = $DB->get_records_sql("SELECT id, $column as c FROM {{$table}} WHERE $column LIKE :col", array(
			"col" => $raw_node_data . "%"
		));
	}

	if (count($data) > 20) {
		return grab_left_set($node, $table, $column, $prefix, $left + 1, "WHERE $column LIKE :col", array(
			"col" => $raw_node_data . "%"
		));
	}

	$out = array();
	foreach ($data as $datum) {
		$url = new \moodle_url("/local/connect/browse/" . $file . ".php", array("id" => $datum->id));
		$out[] = array(
			"id" => "{$prefix}_{$datum->c}",
			"parent" => $node,
			"text" => $datum->c,
			"icon" => false,
			"a_attr" => array("href" => $url->out(true))
		);
	}

	return $out;
}

/**
 * Returns a set of LEFTed data for tree view display
 */
function grab_left_set($node, $table, $column, $prefix, $left = 1, $where = '', $params = array()) {
	global $DB;
	$data = $DB->get_records_sql("SELECT LEFT(t.$column, $left) as c FROM {{$table}} t $where GROUP BY c", $params);

	$out = array();
	foreach ($data as $datum) {
		$out[] = array(
			"id" => "{$prefix}_{$datum->c}",
			"parent" => $node,
			"text" => $datum->c . "...",
			"children" => true
		);
	}

	return $out;
}

if ($node == 'courses') {
	// Grab the courses.
	$out = grab_left_set($node, "connect_course", "module_code", "c", 2);
}

if (strpos($node, "c_") === 0) {
	// Grab a set, see how many there are and decide what to do.
	$raw_node_data = substr($node, 2);
	$out = grab_set("course", $node, $raw_node_data, "connect_course", "module_code", "c", 2);
}

if ($node == 'teachers' || $node == 'convenors' || $node == 'students') {
	// Basically the alphabet...
	$role = substr($node, 0, -1);
	$roleid = $DB->get_field('connect_role', 'id', array(
		'name' => $role
	));
	$extra_sql = 'INNER JOIN {connect_enrolments} ce ON ce.userid=t.id WHERE ce.roleid = :roleid';
	$out = grab_left_set($node, "connect_user", "login", "u_$role", 1, $extra_sql, array(
		"roleid" => $roleid
	));
}

if (strpos($node, "u_") === 0) {
	$raw_type_data = substr($node, strpos($node, '_') + 1, strrpos($node, '_') - 2);
	$roleid = $DB->get_field('connect_role', 'id', array(
		'name' => $raw_type_data
	));

	$raw_node_data = substr($node, strrpos($node, '_') + 1);

	$data = $DB->get_records_sql("SELECT u.id, u.login as c FROM {connect_user} u INNER JOIN {connect_enrolments} ce ON ce.userid=u.id WHERE u.login LIKE :col AND ce.roleid = :roleid", array(
		"col" => $raw_node_data . "%",
		"roleid" => $roleid
	));

	$out = grab_set("user", $node, $raw_node_data, "connect_user", "login", substr($node, 0, strrpos($node, '_') - 2), strlen($raw_node_data), $data);
}

echo $OUTPUT->header();
echo json_encode($out);
