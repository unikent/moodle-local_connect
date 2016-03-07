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
 * Grabs all courses out of SITS.
 */
class courses extends \local_connect\sds\courses
{
    use sql_helper;

    /**
     * Grab out of SITS.
     *
     * @todo moodle_alternative_code
     */
    public function get_all($rowcallback) {
        global $CFG;

        $sql = <<<SQL
            SELECT * FROM vw_moodle_modules WHERE academic_year = {$CFG->connect->session_code}
SQL;

        $this->get_all_sql($sql, $rowcallback);
    }

    /**
     * Cleanup a course.
     */
    protected function clean_row($row) {
        $row = (object)$row;

        if (empty($row->campus) || empty($row->module_week_beginning) || empty($row->module_length)) {
            return null;
        }

        $row->session_code = $row->academic_year;
        $row->week_beginning_date = strtotime($row->week_beginning_date);
        $row->week_beginning_date = strftime("%Y-%m-%d", $row->week_beginning_date);

        return $row;
    }

    /**
     * New Campuses
     */
    protected function sync_new_campus() {
        global $DB;

        echo "  - Migrating new campus\n";

        $sql = '
            INSERT INTO {connect_campus} (shortname, name)
            (
                SELECT c.campus, c.campus_desc
                FROM {tmp_connect_courses} c
                LEFT OUTER JOIN {connect_campus} cc
                    ON cc.shortname = c.campus
                WHERE cc.shortname IS NULL
                GROUP BY c.campus
            )
        ';

        $DB->execute($sql);

        // Update campus IDS in temptable.
        $DB->execute('UPDATE {tmp_connect_courses} c
            INNER JOIN {connect_campus} cc ON c.campus=cc.shortname
            SET c.campus = cc.id');
    }
}
