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
 * This page is an AJAX placeholder for users who wish to
 * fix their enrolments.
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require(dirname(__FILE__) . '/../../config.php');
require_once('locallib.php');

global $PAGE, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/enrolment.php');

$PAGE->set_title("Enrolment");
$PAGE->set_heading("Enrolment");

if (!\local_connect\util\helpers::enable_new_features()) {
    print_error('new_feature_disabled', 'local_connect');
}

$PAGE->requires->js_init_call('M.local_enrolment.init', array(), false, array(
    'name' => 'local_enrolment',
    'fullpath' => '/local/connect/scripts/enrolments.js',
    'requires' => array("node", "io", "dump", "json-parse")
));

require_login();

if (!\local_connect\util\helpers::is_enabled()) {
    print_error('connect_disabled', 'local_connect');
}

echo $OUTPUT->header();

echo $OUTPUT->heading("Fixing your enrolments");

echo $OUTPUT->box_start('enrolmentbox');
    echo '<p>Please wait whilst we check your enrolments...</p>';
    echo $OUTPUT->pix_icon('i/loading_small', "Fixing your enrolments...");
echo $OUTPUT->box_end();

echo $OUTPUT->footer();