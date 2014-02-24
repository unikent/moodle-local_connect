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

defined('MOODLE_INTERNAL') || die;

function xmldb_local_connect_install() {
    global $CFG;

    create_role(
        "Student (SDS)",
        "sds_student",
        "Students generally have fewer privileges within a course.",
        "student"
    );

    create_role(
    	"Teacher (SDS)",
    	"sds_teacher",
    	"Teachers can do anything within a course, including changing the activities and grading students.",
    	"editingteacher"
    );

    create_role(
        "Convenor (SDS)",
        "convenor",
        "A Convenor has the same permissions as a teacher, but can manually enrol teachers.",
        "editingteacher"
    );
}
