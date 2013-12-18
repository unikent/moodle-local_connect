<?php
/**
 * This synchs all groups between Connect and Moodle
 */

define('CLI_SCRIPT', true);

require (dirname(__FILE__) . '/../../../config.php');
require_once (dirname(__FILE__) . '/../../../group/lib.php');
require_once ($CFG->libdir . '/clilib.php');

print "Running group creations...\n";

// Select all the groups that are in Connect and not in Moodle
$sql = 'SELECT mg.id, g.chksum, g.group_desc, g.moodle_id, c.moodle_id as course_id FROM connect_2013.groups g
			JOIN connect_2013.courses c
				ON g.module_delivery_key = c.module_delivery_key
				AND g.session_code = c.session_code
			LEFT OUTER JOIN {groups} mg
				ON g.group_desc = mg.`name`
				AND c.moodle_id = mg.courseid
		WHERE c.moodle_id is not null
		GROUP BY g.id_chksum
		HAVING mg.id IS NULL;';
$objs = $DB->get_recordset_sql($sql);

// For each of these...
foreach ($objs as $obj) {
	$data = new stdClass;
	$data->name = $obj->group_desc;
	$data->courseid = $obj->course_id;
	$data->description = '';

	try {
		// Create the group
		$group = groups_create_group($data);
		if ($group === false) {
			throw new Exception("Could not create group '{$data->name}'!");
		}

		// Get groupings
		$grouping = $DB->get_record('groupings', array(
			'name' => 'Seminar groups',
			'courseid' => $group
		));

		// Create groupings or extract ID
		if (!$grouping) {
			$data->name = "Seminar groups";
			$grouping = groups_create_grouping($data);
		} else {
			$grouping = $grouping->id;
		}

		// Grab all matching groupings attached to the group
		$grouping_groups = $DB->get_record('groupings_groups', array(
			'groupid' => $group,
			'groupingid' => $grouping
		));

		// Assign grouping to the group
		if (!$grouping_groups) {
			groups_assign_grouping($grouping, $group);
		}

		// Finish group
		print "   -> Created group '{$group}'!\n";
	} catch (Exception $e) {
		print "   -> Failed group '{$data->name}'!\n";
		print $e->getMessage();
	}
}

print "Finished!\n";