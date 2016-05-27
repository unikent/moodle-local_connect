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
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect\sits;

defined('MOODLE_INTERNAL') || die();

/**
 * Grabs all enrolments out of SITS.
 */
class enrolments extends \local_connect\sds\enrolments
{
    use sql_helper;

    /**
     * Grab teachers out of SDS.
     */
    public function get_all_teachers($rowcallback) {
        global $CFG;

        $sql = <<<SQL
            SELECT * FROM vw_moodle_lecturers WHERE academic_year = {$CFG->connect->session_code}
SQL;

        $this->get_all_sql($sql, $rowcallback);
    }

    /**
     * Grab convenors out of SDS.
     */
    public function get_all_convenors($rowcallback) {
        global $CFG;

        $sql = <<<SQL
            SELECT * FROM vw_moodle_conveners WHERE academic_year = {$CFG->connect->session_code}
SQL;

        $this->get_all_sql($sql, $rowcallback);
    }

    /**
     * Grab students out of SDS.
     */
    public function get_all_students($rowcallback) {
        global $CFG;

        $sql = <<<SQL
            SELECT * FROM vw_moodle_students WHERE academic_year = {$CFG->connect->session_code}
SQL;

        $this->get_all_sql($sql, $rowcallback);
    }
}
