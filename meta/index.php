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

// Allow admins to regenerate list.
if (!has_capability('moodle/site:config', \context_system::instance())) {
    print_error('Access Denied');
}

admin_externalpage_setup('reportconnectmeta', '', null, '', array('pagelayout' => 'report'));

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading("Connect Meta Enrolment Manager");

// Output footer.
echo $OUTPUT->footer();