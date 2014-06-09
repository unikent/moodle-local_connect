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
$PAGE->set_url('/local/connect/meta/new.php');

admin_externalpage_setup('reportconnectmeta', '', null, '', array('pagelayout' => 'report'));

$stage = optional_param('stage', 1, PARAM_INT);

$form = new \local_connect\forms\newmeta($stage);

if ($stage === 3 && ($data = $form->get_data())) {
    $DB->insert_record('connect_meta', array(
        'objectid' => $data->objectid,
        'objecttype' => $data->objecttype,
        'courseid' => $data->courseid
    ));
    redirect($CFG->wwwroot . '/local/connect/meta/index.php');
}

if ($form->is_cancelled()) {
    redirect($CFG->wwwroot . '/local/connect/meta/index.php');
}

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading("Create new meta set");

print \html_writer::tag("p", "Stage {$stage}/3");

$form->display();

// Output footer.
echo $OUTPUT->footer();