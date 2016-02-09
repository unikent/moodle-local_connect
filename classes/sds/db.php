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

namespace local_connect\sds;

defined('MOODLE_INTERNAL') || die();

/**
 * DB helper.
 *
 * @deprecated
 */
class db
{
    private static $dbinstance;

    /**
     * Return an instance of the SDS DB connection.
     */
    public static function instance() {
        global $CFG;

        if (!isset(static::$dbinstance)) {
            $instance = mssql_connect($CFG->sdsdatabase['host'], $CFG->sdsdatabase['username'], $CFG->sdsdatabase['password']);
            mssql_select_db($CFG->sdsdatabase['database']);

            // Allow quoted identifiers.
            $sql = "SET QUOTED_IDENTIFIER ON";
            mssql_query($sql, $instance);

            // Force ANSI nulls so the NULL check was done by IS NULL and NOT IS NULL
            // instead of equal(=) and distinct(<>) symbols.
            $sql = "SET ANSI_NULLS ON";
            mssql_query($sql, $instance);

            // Force ANSI warnings so arithmetic/string overflows will be
            // returning error instead of transparently truncating data.
            $sql = "SET ANSI_WARNINGS ON";
            mssql_query($sql, $instance);

            // Concatenating null with anything MUST return NULL.
            $sql = "SET CONCAT_NULL_YIELDS_NULL  ON";
            mssql_query($sql, $instance);

            // Set transactions isolation level to READ_COMMITTED
            // prevents dirty reads when using transactions +
            // is the default isolation level of MSSQL
            // Requires database to run with READ_COMMITTED_SNAPSHOT ON.
            $sql = "SET TRANSACTION ISOLATION LEVEL READ COMMITTED";
            mssql_query($sql, $instance);

            static::$dbinstance = $instance;
        }

        return static::$dbinstance;
    }
}
