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
 * Sync base.
 */
trait sql_helper
{
    /**
     * Grab records out of SDS.
     */
    public function get_all_sql($sql, $rowcallback, $batchsize = 1000) {
        global $SDSDB;

        $rows = array();
        $rs = $SDSDB->get_recordset_sql($sql);
        foreach ($rs as $row) {
            $rows[] = $row;

            if (count($rows) > $batchsize) {
                $rowcallback($rows);
                $rows = array();
            }
        }

        if (!empty($rows)) {
            $rowcallback($rows);
        }

        $rs->close();
    }
}
