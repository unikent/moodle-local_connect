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
 * SDS MSSQL DB connector.
 */
class db {
    /**
     * Obtain the connector.
     */
    public static function obtain() {
        global $CFG;

        $mssql = mssql_connect($CFG->kent->sdsdb['host'], $CFG->kent->sdsdb['username'], $CFG->kent->sdsdb['password'], true);
        mssql_select_db('studb', $mssql);

        $sql = "SET ANSI_NULLS ON";
        $result = mssql_query($sql, $mssql);

        $sql = "SET ANSI_WARNINGS ON";
        $result = mssql_query($sql, $mssql);

        return $mssql;
    }
}