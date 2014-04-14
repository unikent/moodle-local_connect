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
 * This allows a user to browse and analyse data in connect.
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require (dirname(__FILE__) . '/../../../config.php');

/**
 * Page setup.
 */
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/browse/index.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('connectbrowse', 'local_connect'));
$PAGE->navbar->add("Connect Browser");

/**
 * Script setup.
 */
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('migrate');
$PAGE->requires->js('/local/connect/scripts/jstree.min.js');
$PAGE->requires->js('/local/connect/scripts/browse0.js');
$PAGE->requires->css('/local/connect/styles/jtree.css');

/**
 * Check capabilities.
 */
if (!has_capability('moodle/site:config', context_system::instance())) {
    print_error('accessdenied', 'admin');
}

/**
 * And, the actual page.
 */
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connectbrowse', 'local_connect'));

echo '<input type="text" value="" placeholder="Search" id="cb_search">';
echo '<div id="connect_browser"></div>';

echo $OUTPUT->footer();