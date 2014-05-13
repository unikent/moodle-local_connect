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

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/meta/index.php');

$PAGE->requires->css('/local/connect/styles/meta.css');

// Allow admins to regenerate list.
if (!has_capability('moodle/site:config', \context_system::instance())) {
    print_error('Access Denied');
}

$page    = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);

admin_externalpage_setup('reportconnectmeta', '', null, '', array('pagelayout' => 'report'));

// Did we get a delete meta request?
$form = new \local_connect\forms\deletemeta(null);
if ($data = $form->get_data()) {
    if (!empty($data->id)) {
        $DB->delete_records('connect_meta', array(
            'id' => $data->id
        ));
    }
}

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading("Connect Meta Enrolment Manager");

// New link.
echo \html_writer::tag('a', 'New', array(
    'href' => 'new.php'
));

// Data table.
$table = new \html_table();
$table->head = array("Object", "Moodle Course", "Enrolments", "Action");
$table->attributes = array('class' => 'admintable generaltable');
$table->data = array();

$deleteform = '';

$records = $DB->get_records('connect_meta', null, '', 'id', $page * $perpage, $perpage);
foreach ($records as $record) {
    $obj = \local_connect\meta::get($record->id);
    $enrolments = $obj->enrolments;

    $course = new \html_table_cell(\html_writer::tag('a', $obj->course->shortname, array(
        'href' => $CFG->wwwroot . '/course/view.php?id=' . $obj->courseid,
        'target' => '_blank'
    )));

    // Create the delete meta form.
    $form = new \local_connect\forms\deletemeta($obj->id);

    $table->data[] = array(
        $obj,
        $course,
        count($enrolments),
        $form->render()
    );
}

echo \html_writer::table($table);

// Paging bar.
$total = $DB->count_records('connect_meta');
$baseurl = new moodle_url('/local/connect/meta/index.php', array('perpage' => $perpage));
echo $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);

// Output footer.
echo $OUTPUT->footer();