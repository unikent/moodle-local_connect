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
 * Grabs all groups out of SDS.
 */
class groups {
    /**
     * Grab out of SDS.
     */
    public static function get_all() {
        global $CFG, $SDSDB;

        db::obtain();

        $sql = <<<SQL
            SELECT DISTINCT
              ltrim(rtrim(cg.group_id)) AS group_id,
              ltrim(rtrim(cg.group_desc)) AS group_desc,
              ltrim(rtrim(dgm.module_delivery_key)) AS module_delivery_key,
              ltrim(rtrim(dgm.session_code)) AS session_code
            FROM d_group_module AS dgm
              INNER JOIN c_groups AS cg ON cg.parent_group = dgm.group_id
              LEFT JOIN l_ukc_group AS lug on lug.group_id = cg.group_id
              LEFT JOIN b_details AS bd on bd.ukc = lug.ukc
            WHERE (dgm.session_code = {$CFG->connect->session_code})
              AND (cg.group_type = 'S')
              AND bd.email_address != ''
SQL;

        return $SDSDB->get_records_sql($sql);
    }
}