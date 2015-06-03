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

require_once(dirname(__FILE__) . '/../../config.php');

require_login();

if (!\local_connect\util\helpers::is_enabled()) {
    print_error('connect_disabled', 'local_connect');
}

if (!\local_connect\util\helpers::can_course_manage()) {
    print_error('accessdenied', 'local_connect');
}

// Initial setup.
$sitecontext = context_system::instance();
$catpermissions = \local_connect\util\helpers::get_connect_course_categories();

// Page setup.
$PAGE->set_context($sitecontext);
$PAGE->set_url('/local/connect/index.php');
$PAGE->set_pagelayout('datool');
$PAGE->set_title("Departmental administration");

// JQuery.
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('migrate');
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->jquery_plugin('blockui', 'theme_kent');
$PAGE->requires->jquery_plugin('dataTables', 'theme_kent');

// Our JS.
$PAGE->requires->js('/local/connect/scripts/underscore-min.js');
$PAGE->requires->js('/local/connect/scripts/date-en-GB.js');
$PAGE->requires->js('/local/connect/scripts/button-loader.js');
$PAGE->requires->js('/local/connect/scripts/connect.js');
$PAGE->requires->js('/local/connect/scripts/app.js');
$cats = has_capability('local/connect:manage', $sitecontext) ? "" : implode(',', array_keys($catpermissions));
$PAGE->requires->js_init_call('connect_load', array(
    $cats
));

// Our CSS.
$PAGE->requires->css('/local/connect/styles/styles.min.css');

// And the page itself.

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connectreport', 'local_connect'));

$renderer = $PAGE->get_renderer('local_connect');
$renderer->render_index();
$renderer->render_index_js($catpermissions);

echo $OUTPUT->footer();
