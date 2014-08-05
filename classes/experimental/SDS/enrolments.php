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
            (
                ltrim(rtrim(session_code)) + '|' +
                ltrim(rtrim(module_delivery_key)) + '|' +
                ltrim(rtrim(login))
            ) as chksum
            , ltrim(rtrim(login)) as login
            , ltrim(rtrim(staff)) as staff
            , ltrim(rtrim(module_delivery_key)) as module_delivery_key
            , ltrim(rtrim(session_code)) as session_code
          FROM d_timetable
          WHERE (session_code = $sessioncode) and login is not null and login != ''
SQL;

        $teachers = array();

        $objects = $SDSDB->get_records_sql($sql);
        foreach ($objects as $teacher) {
            $logins = explode(',', $teacher->login);
            $names = explode(',', $teacher->staff);

            if (count($names) != count($logins)) {
                continue;
            }

            for ($i = 0; $i < count($logins); $i++) {
                $login = $logins[$i];
                $name = $names[$i];
                $name = explode(' ', $name, 3);

                $chksum = md5("{$teacher->session_code}|{$teacher->module_delivery_key}|{$login}|teacher");

                $data = array(
                    'chksum' => $chksum,
                    'login' => $login,
                    'module_delivery_key' => $teacher->module_delivery_key,
                    'session_code' => $teacher->session_code,
                    'title' => '',
                    'surname' => '',
                    'givenname' => ''
                );

                if (isset($name[0])) {
                    $data['surname'] = $name[0];
                }

                if (isset($name[1])) {
                    $data['title'] = $name[1];
                }

                if (isset($name[2])) {
                    $data['givenname'] = $name[2];
                }

                $teachers[] = (object)$data;
            }
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