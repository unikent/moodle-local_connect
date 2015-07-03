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

namespace local_connect;

defined('MOODLE_INTERNAL') || die();

/**
 * New Connect core to simplify things elsewhere and provide
 * a single, central API for Connect.
 */
class core {
    /**
     * Return all my SDS departments.
     */
    public function get_my_departments() {
        global $DB, $USER;

        $sql = <<<SQL
            SELECT ra.id, cc.idnumber
            FROM {role_assignments} ra
            INNER JOIN {role} r
                ON r.shortname = :rshort
                AND r.id=ra.roleid
            INNER JOIN {context} ctx
                ON ctx.contextlevel = :contextlevel
                AND ctx.id = ra.contextid
            INNER JOIN {course_categories} cc
                ON cc.id = ctx.instanceid
            WHERE ra.userid = :userid
SQL;

        $records = $DB->get_recordset_sql($sql, array(
            'rshort' => 'dep_admin',
            'contextlevel' => CONTEXT_COURSECAT,
            'userid' => $USER->id
        ));

        $map = category::get_map_table();
        $departments = array();
        foreach ($records as $record) {
            foreach ($map as $mapentry) {
                if ($record->idnumber == $mapentry['idnumber']) {
                    $departments[] = $mapentry['department'];
                }
            }
        }

        $records->close();

        return array_unique($departments);
    }

    /**
     * Return all my SDS courses.
     */
    public function get_my_courses() {
        $courses = array();

        // Add all courses I am enrolled in.
        $enrolments = enrolment::get_my_enrolments();
        foreach ($enrolments as $enrolment) {
            $courses[$enrolment->courseid] = $enrolment->course;
        }

        // Add department maps.
        $departments = $this->get_my_departments();
        foreach ($departments as $department) {
            // Grab related courses.
            $rs = course::get_by('department', $department);
            foreach ($rs as $course) {
                $courses[$course->id] = $course;
            }
        }

        return $courses;
    }
}