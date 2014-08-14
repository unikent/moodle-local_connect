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
 * Grabs all enrolments out of SDS.
 */
class enrolments {
    /**
     * Grab out of SDS.
     */
    public static function get_all_teachers($sessioncode) {
        global $SDSDB;

        db::obtain();

        $sql = <<<SQL
            SELECT DISTINCT
              (ltrim(rtrim(session_code)) + '|' + ltrim(rtrim(mdk)) + '|' + ltrim(rtrim(lecturerid)) + '|teacher') as chksum
              , ltrim(rtrim(lecturerid)) as login
              , ltrim(rtrim(title)) as title
              , ltrim(rtrim(initials)) as initials
              , ltrim(rtrim(surname)) as family_name
              , ltrim(rtrim(mdk)) as module_delivery_key
              , ltrim(rtrim(session_code)) as session_code
            FROM v_moodle_data_export_new
            WHERE (session_code = $sessioncode) and lecturerid is not null and lecturerid != ''
SQL;

        $teachers = $SDSDB->get_records_sql($sql);
        foreach ($teachers as $teacher) {
            $teacher->chksum = md5($teacher->chksum);
        }

        return $teachers;
    }
    /**
     * Grab out of SDS.
     */
    public static function get_all_convenors($sessioncode) {
        global $SDSDB;

        db::obtain();

        $sql = <<<SQL
            SELECT DISTINCT
              (
                '$sessioncode|' +
                ltrim(rtrim(dmc.module_delivery_key)) + '|' +
                ltrim(rtrim(cs.login)) + '|convenor'
              )  as chksum
              , ltrim(rtrim(cs.login)) as login
              , ltrim(rtrim(cs.title)) as title
              , ltrim(rtrim(cs.initials)) as initials
              , ltrim(rtrim(cs.family_name)) as family_name
              , '' as ukc
              , ltrim(rtrim(dmc.module_delivery_key)) as module_delivery_key
              , 'convenor' as role
              , '$sessioncode' as session_code
            FROM d_module_convener AS dmc
              INNER JOIN c_staff AS cs ON dmc.staff = cs.staff
              INNER JOIN m_current_values mcv ON 1=1
              INNER JOIN c_session_dates csd ON csd.session_code = $sessioncode + 1
            WHERE (
                dmc.staff_function_end_date IS NULL
                OR dmc.staff_function_end_date > CURRENT_TIMESTAMP
                OR (mcv.session_code > $sessioncode
                AND dmc.staff_function_end_date >= mcv.rollover_date
                AND CURRENT_TIMESTAMP < csd.session_start)
            ) AND cs.login != ''
SQL;

        $objects = $SDSDB->get_records_sql($sql);

        foreach ($objects as $convenor) {
            $convenor->chksum = md5($convenor->chksum);
        }

        return $objects;
    }
    /**
     * Grab out of SDS.
     */
    public static function get_all_students($sessioncode) {
        global $SDSDB;

        db::obtain();

        $sql = <<<SQL
            SELECT DISTINCT
              (
                ltrim(rtrim(bm.session_taught)) + '|' +
                ltrim(rtrim(cast(bm.module_delivery_key as varchar))) + '|' +
                ltrim(rtrim(bd.email_address)) + '|student'
              ) as chksum
              , ltrim(rtrim(bd.email_address)) as login
              , '' as title
              , ltrim(rtrim(bd.initials)) as initials
              , ltrim(rtrim(bd.family_name)) as family_name
              , ltrim(rtrim(bd.ukc)) as ukc
              , ltrim(rtrim(bm.module_delivery_key)) as module_delivery_key
              , ltrim(rtrim(bm.session_taught)) as session_code
            FROM b_details AS bd
              INNER JOIN b_module AS bm ON bd.ukc = bm.ukc
                AND bd.academic IN ('A','J','P','R','T','W','Y','H')
                AND bd.email_address <> ''
                AND (bm.session_taught = '$sessioncode') AND (bm.module_registration_status IN ('R','U'))
                AND bd.email_address != ''
SQL;

        $objects = $SDSDB->get_records_sql($sql);

        foreach ($objects as $student) {
            $student->chksum = md5($student->chksum);
        }

        return $objects;
    }
}