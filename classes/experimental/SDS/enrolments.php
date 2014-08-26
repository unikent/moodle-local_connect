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
     * Grab teachers out of SDS.
     */
    public static function get_all_teachers() {
        global $CFG, $SDSDB;

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
              , 'teacher' as role
            FROM v_moodle_data_export
            WHERE (session_code = {$CFG->connect->session_code}) and lecturerid is not null and lecturerid != ''
SQL;

        $teachers = $SDSDB->get_records_sql($sql);
        foreach ($teachers as $teacher) {
            $teacher->chksum = md5($teacher->chksum);
        }

        return $teachers;
    }

    /**
     * Grab convenors out of SDS.
     */
    public static function get_all_convenors() {
        global $CFG, $SDSDB;

        db::obtain();

        $sql = <<<SQL
            SELECT DISTINCT
              (
                '{$CFG->connect->session_code}|' +
                ltrim(rtrim(dmc.module_delivery_key)) + '|' +
                ltrim(rtrim(cs.login)) + '|convenor'
              )  as chksum
              , ltrim(rtrim(cs.login)) as login
              , ltrim(rtrim(cs.title)) as title
              , ltrim(rtrim(cs.initials)) as initials
              , ltrim(rtrim(cs.family_name)) as family_name
              , '' as ukc
              , ltrim(rtrim(dmc.module_delivery_key)) as module_delivery_key
              , '{$CFG->connect->session_code}' as session_code
              , 'convenor' as role
            FROM d_module_convener AS dmc
              INNER JOIN c_staff AS cs ON dmc.staff = cs.staff
              INNER JOIN m_current_values mcv ON 1=1
              INNER JOIN c_session_dates csd ON csd.session_code = {$CFG->connect->session_code} + 1
            WHERE (
                dmc.staff_function_end_date IS NULL
                OR dmc.staff_function_end_date > CURRENT_TIMESTAMP
                OR (mcv.session_code > {$CFG->connect->session_code}
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
     * Grab students out of SDS.
     */
    public static function get_all_students() {
        global $CFG, $SDSDB;

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
              , 'student' as role
            FROM b_details AS bd
              INNER JOIN b_module AS bm ON bd.ukc = bm.ukc
                AND bd.academic IN ('A','J','P','R','T','W','Y','H')
                AND bd.email_address <> ''
                AND (bm.session_taught = '{$CFG->connect->session_code}') AND (bm.module_registration_status IN ('R','U'))
                AND bd.email_address != ''
SQL;

        $objects = $SDSDB->get_records_sql($sql);

        foreach ($objects as $student) {
            $student->chksum = md5($student->chksum);
        }

        return $objects;
    }

    /**
     * Get a temptable for the enrolments sync.
     */
    private function get_temp_table() {
        global $CFG;

        require_once($CFG->libdir . '/ddllib.php');

        $table = new \xmldb_table('tmp_connect_enrolments');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('chksum', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $table->add_field('login', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('initials', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('family_name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('ukc', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('role', XMLDB_TYPE_CHAR, '15', null, XMLDB_NOTNULL, null, null);
        $table->add_field('module_delivery_key', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $table->add_field('session_code', XMLDB_TYPE_CHAR, '4', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('chksum', XMLDB_INDEX_UNIQUE, array('chksum'));
        $table->add_index('role', XMLDB_INDEX_NOTUNIQUE, array('role'));
        $table->add_index('module_delivery_key', XMLDB_INDEX_NOTUNIQUE, array('module_delivery_key'));
        $table->add_index('session_code', XMLDB_INDEX_NOTUNIQUE, array('session_code'));

        return $table;
    }

    /**
     * Get some sync stats.
     */
    public function print_stats() {
        global $CFG, $DB;

        $convenors = $DB->count_records('tmp_connect_enrolments', array(
            'role' => 'convenor'
        ));

        $teachers = $DB->count_records('tmp_connect_enrolments', array(
            'role' => 'teacher'
        ));

        $students = $DB->count_records('tmp_connect_enrolments', array(
            'role' => 'student'
        ));

        echo ($convenors + $teachers + $students) . " enrolments found.\n";
        echo "  - $convenors convenors\n";
        echo "  - $teachers teachers\n";
        echo "  - $students students\n";
    }

    /**
     * Delete all teachers who are also a convenor.
     */
    private function clean_tmp() {
        global $DB;

        $objs = $DB->get_records_sql('
            SELECT t.id, t.login, t.module_delivery_key
            FROM {tmp_connect_enrolments} t
            WHERE t.role IN (:teacher, :convenor)
            GROUP BY t.login, t.module_delivery_key
            HAVING COUNT(role) > 1
        ', array(
            'teacher' => 'teacher',
            'convenor' => 'convenor'
        ));

        foreach ($objs as $obj) {
            $DB->delete_records('tmp_connect_enrolments', array(
                'login' => $obj->login,
                'module_delivery_key' => $obj->module_delivery_key,
                'role' => 'teacher'
            ));
        }
    }

    /**
     * Sync New Users
     */
    private function sync_new_users() {
        global $DB;

        echo "  - Migrating new users\n";

        $sql = "INSERT INTO {connect_user} (ukc, login, title, initials, family_name) (
            SELECT e.ukc, e.login, COALESCE(e.title, ''), COALESCE(e.initials, ''), COALESCE(e.family_name, '')
            FROM {tmp_connect_enrolments} e
            LEFT OUTER JOIN {connect_user} u
                ON u.login=e.login
            WHERE u.id IS NULL
            GROUP BY e.login
        )";

        return $DB->execute($sql);
    }

    /**
     * Sync Updated Users
     */
    private function sync_updated_users() {
        global $DB;

        echo "  - Migrating updated users\n";

        $sql = "REPLACE INTO {connect_user} (id, ukc, login, title, initials, family_name) (
            SELECT u.id, e.ukc, u.login, COALESCE(e.title, ''), COALESCE(e.initials, ''), COALESCE(e.family_name, '')
            FROM {tmp_connect_enrolments} e
            INNER JOIN {connect_user} u
                ON u.login=e.login
            WHERE u.id IS NULL
            GROUP BY e.login
        )";

        return $DB->execute($sql);
    }

    /**
     * Sync enrolments with Moodle.
     */
    public function sync() {
        global $CFG, $DB;

        // Create a temp table.
        $table = $this->get_temp_table();
        $dbman = $DB->get_manager();
        $dbman->create_temp_table($table);

        // Load data into the temp table.
        echo "Loading teacher data from SDS...\n";
        $DB->insert_records('tmp_connect_enrolments', $this->get_all_teachers());
        echo "Loading convenor data from SDS...\n";
        $DB->insert_records('tmp_connect_enrolments', $this->get_all_convenors());
        echo "Cleaning duplicates...\n";
        $this->clean_tmp();

        echo "Loading student data from SDS...\n";
        $DB->insert_records('tmp_connect_enrolments', $this->get_all_students());

        $this->print_stats();

        // Sync.
        $this->sync_updated_users();
        $this->sync_new_users();

        // Drop the temp table.
        $dbman->drop_table($table);
    }
}