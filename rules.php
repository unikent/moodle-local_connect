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

require_once('../../config.php');

if (!has_capability('moodle/site:config', \context_system::instance())) {
    print_error("Access Denied");
}

$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/local/connect/rules.php');

$rules = $DB->get_records_sql('SELECT c.name as catname, cr.*
	FROM {connect_rules} cr
	INNER JOIN {course_categories} c ON c.id=cr.category
');


$table = new \html_table();
$table->head = array(
    "Rule", "Prefix", "Category", "Action"
);
$table->data = array();
foreach ($rules as $rule) {
    $category = new \html_table_cell(\html_writer::tag('a', $rule->catname, array(
        'href' => $CFG->wwwroot . '/course/index.php?categoryid=' . $rule->category
    )));
    $table->data[] = new \html_table_row(array(
        $rule->id,
        $rule->prefix,
        $rule->category,
        "Do Nothing"
    ));
}


echo $OUTPUT->header();
echo $OUTPUT->heading("Connect Rules");

echo $OUTPUT->box_start('contents');
echo \html_writer::table($table);
echo $OUTPUT->box_end();

echo $OUTPUT->footer();