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
require_once($CFG->libdir . '/accesslib.php');

require_login();

if (!\local_connect\util\helpers::is_enabled()) {
    print_error('connect_disabled', 'local_connect');
}

if (!\local_connect\util\helpers::can_course_manage()) {
    print_error('accessdenied', 'local_connect');
}

// Page setup.
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/index2.php');
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add("Connect Administration");

echo $OUTPUT->header();
echo $OUTPUT->heading("Connect Administration");

$courses = get_user_capability_course('moodle/course:update');

print 'Coming Soon.';

echo $OUTPUT->footer();