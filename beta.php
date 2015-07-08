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
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

if (!\local_connect\util\helpers::is_enabled()) {
    print_error('connect_disabled', 'local_connect');
}

require_login();

// Page setup.
$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/local/connect/beta.php');
$PAGE->set_title('SDS push tool (beta)');

echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->title);

$connect = new \local_connect\core();
$courses = $connect->get_my_courses();

$renderer = $PAGE->get_renderer('local_connect');
$renderer->render_sds_list($courses);

echo $OUTPUT->footer();
