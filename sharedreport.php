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
 * Local stuff for Moodle Connect
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $PAGE, $OUTPUT, $CFG, $SHAREDB;

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/sharedreport.php');

admin_externalpage_setup('reportconnectsharedreport', '', null, '', array('pagelayout' => 'report'));

// Dont show anything if there is nothing to show!
if (!\local_connect\utils::enable_sharedb()) {
	print_error("Shared Moodle has not been enabled on this system, so there is nothing to show!");
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("sharedreport", "local_connect"));
echo $OUTPUT->box_start('reportbox');

$table = new \html_table();
$table->head = array("Environment", "Distribution", "Course ID", "Shortname", "Fullname");
$table->attributes = array('class' => 'admintable generaltable');
$table->data = array();

// Grab a list of courses we can see.
$records = $SHAREDB->get_records('course_list');
foreach ($records as $record) {
	$table->data[] = new \html_table_row(array(
		$record->moodle_env,
		$record->moodle_dist,
		$record->moodle_id,
		$record->shortname,
		$record->fullname,
	));
}

echo \html_writer::table($table);

// Allow admins to regenerate list.
if (has_capability('moodle/site:config', \context_system::instance())) {
	echo '<p><a href="'.$CFG->wwwroot.'/local/connect/regenerate_shared_list.php">Regenerate list</a> (Warning: Do not do this unless you know exactly what it means.)</p>';
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
