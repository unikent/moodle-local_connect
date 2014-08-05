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
        global $CFG, $SDSDB;

        if (isset($SDSDB)) {
            return;
        }

        if (!$SDSDB = \moodle_database::get_driver_instance($CFG->kent->sdsdb['driver'],
                                                            $CFG->kent->sdsdb['library'],
                                                            true)) {
            throw new \dml_exception('dbdriverproblem', "Unknown driver for SDS");
        }

        $SDSDB->connect(
            $CFG->kent->sdsdb['host'],
            $CFG->kent->sdsdb['user'],
            $CFG->kent->sdsdb['pass'],
            $CFG->kent->sdsdb['name'],
            $CFG->kent->sdsdb['prefix'],
            $CFG->kent->sdsdb['options']
        );

        return $SDSDB;
    }
}