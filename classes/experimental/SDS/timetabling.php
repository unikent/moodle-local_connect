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
 * Moodle Connect Experimental Files
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect\experimental\SDS;

/**
 * Grabs all timetabling information out of SDS.
 */
class timetabling {
    /**
     * Grab out of SDS.
     */
    public static function get_week_sessions() {
        global $CFG, $SDSDB;

        db::obtain();

        $sql = <<<SQL
            SELECT DISTINCT
              ltrim(rtrim(cast(cswb.session_code as varchar))) + '|' + ltrim(rtrim(cast(cswb.week_beginning as varchar))) as chksum,
              ltrim(rtrim(cast(cswb.session_code as varchar))) as session_code,
              ltrim(rtrim(cast(cswb.week_beginning as varchar))) as week_beginning,
              ltrim(rtrim(cast(cswb.week_beginning_date as varchar))) as week_beginning_date,
              ltrim(rtrim(cast(cswb.week_number as varchar))) as week_number
            FROM c_session_week_beginning cswb
            WHERE cswb.session_code = {$CFG->connect->session_code}
SQL;

        return $SDSDB->get_records_sql($sql);
    }
}