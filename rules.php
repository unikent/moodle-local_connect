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
require_once($CFG->libdir.'/adminlib.php');

if (!has_capability('moodle/site:config', \context_system::instance())) {
    print_error("Access Denied");
}

admin_externalpage_setup('connectrules', '', null, '', array('pagelayout' => 'report'));

$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/local/connect/rules.php');

$id = optional_param('id', 11, PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);

if ($id && $action) {
    $rule = \local_connect\rule::get($id);
    switch ($action) {
        case 'up':
            $rule->increase_priority();
        break;
        case 'down':
            $rule->decrease_priority();
        break;
        case 'delete':
            $rule->delete();
        break;
    }
}

// Create a new rule form.
$form = new \local_connect\forms\rule(null, array());
if ($data = $form->get_data()) {
    // Create the rule.
    $DB->insert_record('connect_rules', array(
        "prefix" => $data->prefix,
        "category" => $data->category
    ));
}

// Grab all the rules.
$rules = $DB->get_records_sql('SELECT cr.id, cr.prefix, cr.category, cr.weight, c.name as catname
    FROM {connect_rules} cr
    INNER JOIN {course_categories} c ON c.id=cr.category
    ORDER BY weight DESC
');

$table = new \html_table();
$table->head = array(
    "Rule", "Prefix", "Category", "Weight", "Action"
);
$table->data = array();
foreach ($rules as $rule) {
    $category = new \html_table_cell(\html_writer::tag('a', $rule->catname, array(
        'href' => $CFG->wwwroot . '/course/index.php?categoryid=' . $rule->category,
        'target' => '_blank'
    )));

    $bumpup = \html_writer::tag('a', 'Up', array(
        'href' => $CFG->wwwroot . '/local/connect/rules.php?id=' . $rule->id . '&action=up'
    ));

    $bumpdown = \html_writer::tag('a', 'Down', array(
        'href' => $CFG->wwwroot . '/local/connect/rules.php?id=' . $rule->id . '&action=down'
    ));

    $delete = \html_writer::tag('a', 'Delete', array(
        'href' => $CFG->wwwroot . '/local/connect/rules.php?id=' . $rule->id . '&action=delete'
    ));

    $table->data[] = new \html_table_row(array(
        $rule->id,
        $rule->prefix,
        $category,
        $rule->weight,
        "$bumpup $bumpdown $delete"
    ));
}

echo $OUTPUT->header();
echo $OUTPUT->heading("Connect Rules");

echo $OUTPUT->box_start('contents');
echo \html_writer::table($table);
echo $OUTPUT->box_end();

echo $OUTPUT->heading("New Rule");

$form->display();

echo $OUTPUT->footer();