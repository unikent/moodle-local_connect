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
 * Grabs all courses out of SDS.
 */
class course {
    /**
     * Grab out of SDS.
     */
    public static function get_all($sessioncode) {
        global $SDSDB;

        db::obtain();

        $sql = <<<SQL
            SELECT DISTINCT
                ltrim(rtrim(dmd.module_delivery_key)) as module_delivery_key,
                ltrim(rtrim(dmds.session_code)) as session_code,
                ltrim(rtrim(dmd.delivery_department)) as delivery_department,
              ltrim(rtrim(cmd.module_version)) as module_version,
              ltrim(rtrim(cc.campus)) as campus,
              ltrim(rtrim(cc.campus_desc)) as campus_desc,
              ltrim(rtrim(dmd.module_week_beginning)) as module_week_beginning,
              swb.week_beginning_date as week_beginning_date,
              ltrim(rtrim(cmd.module_length)) as module_length, ltrim(rtrim(cmd.module_title)) as module_title,
              ltrim(rtrim(cmd.module_code)) as module_code,
              syn.data as synopsis,
              lower(CAST(master.dbo.fn_varbintohexsubstring( 0,
                 hashbytes('md5',
                  ltrim(rtrim(dmds.session_code)) + '|' + ltrim(rtrim(cast(dmd.module_delivery_key as varchar)))
                ), 1, 0) as char(32))) as id_chksum
              , CAST(lower(master.dbo.fn_varbintohexsubstring( 0,
                hashbytes('md5',
                  dmds.session_code + dmd.delivery_department
                  + cast(dmd.module_delivery_key as varchar) + cast(cmd.module_version as varchar)
                  + cc.campus + cc.campus_desc + dmd.module_week_beginning + cmd.module_length
                  + cmd.module_title + cmd.module_code + isnull(cast(syn.data as varchar(500)),0)
                ), 1, 0)) as char(32)) as chksum
            FROM d_module_delivery dmd
              INNER JOIN d_module_delivery_session AS dmds
                  ON dmds.module_delivery_key = dmd.module_delivery_key
                  AND dmds.module_status = 'ACTIVE'
              AND dmds.session_code = $sessioncode
              INNER JOIN c_campus AS cc ON dmd.delivery_campus = cc.campus
              INNER JOIN c_module_details AS cmd
                  ON cmd.module_code = dmd.module_code AND cmd.module_version = dmd.module_version
              LEFT JOIN (
                SELECT DISTINCT
                  he_1.module_code, he_1.module_version, cast(he_1.data as varchar(500)) as data
                FROM CLIO.UKC_Reference.dbo.hdb_handbook_entry AS he_1
                WHERE he_1.id IN (
                  SELECT TOP 1 id
                  FROM CLIO.UKC_Reference.dbo.hdb_handbook_entry AS hee_1
                  WHERE hee_1.isactive = 1
                      AND hee_1.display_order = 1
                      AND entry_type = 'S'
                      AND session_code = $sessioncode
                      AND hee_1.module_code = he_1.module_code
                )
              ) AS syn ON syn.module_code=cmd.module_code AND syn.module_version=cmd.module_version
              INNER JOIN c_session_week_beginning AS swb
                  ON swb.week_beginning = dmd.module_week_beginning
                  AND swb.session_code = $sessioncode
            WHERE dmd.delivery_faculty IN ('A','H','S','U')
SQL;

        return $SDSDB->get_records_sql($sql);
    }
}