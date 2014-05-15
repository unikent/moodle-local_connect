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
 * Grabs rollover sources for this installation.
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');

global $CFG, $PAGE, $OUTPUT, $USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/ajax/rollover_sources.php');

if (!isloggedin()) {
    throw new moodleexception("You must be logged in.");
}

if (!\local_connect\util\helpers::is_enabled() || !\local_connect\util\helpers::enable_new_features()) {
    throw new moodleexception("This feature has not been enabled.");
}

$name = required_param('name', PARAM_ALPHANUMEXT);
$source = optional_param('source', $CFG->kent->distribution, PARAM_ALPHANUMEXT);

$data = \local_connect\rollover::get_course_list($source, $name);

$json = array();
foreach ($data as $datum) {
    $json[] = $datum->shortname;
}

echo $OUTPUT->header();
echo json_encode(array_unique($json));