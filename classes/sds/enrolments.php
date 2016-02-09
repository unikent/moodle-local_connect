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
 * Grabs all enrolments out of SDS.
 */
class enrolments extends \core\task\adhoc_task
{
    use sql_helper;

    /**
     * Returns the component name.
     */
    public function get_component() {
        return 'local_connect';
    }

    /**
     * Grab teachers out of SDS.
     */
    public function get_all_teachers($rowcallback) {
        global $CFG;

        $sql = <<<SQL
            SELECT DISTINCT
              ltrim(rtrim(lecturerid)) as login
              , ltrim(rtrim(title)) as title
              , ltrim(rtrim(initials)) as initials
              , ltrim(rtrim(surname)) as family_name
              , ltrim(rtrim(mdk)) as module_delivery_key
              , ltrim(rtrim(session_code)) as session_code
              , 'sds_teacher' as role
            FROM v_moodle_data_export
            WHERE (session_code = {$CFG->connect->session_code}) and lecturerid is not null and lecturerid != ''
SQL;

        $this->get_all_sql($sql, $rowcallback);
    }

    /**
     * Grab convenors out of SDS.
     */
    public function get_all_convenors($rowcallback) {
        global $CFG;

        $codeplus = ((int)$CFG->connect->session_code) + 1;

        $sql = <<<SQL
            SELECT DISTINCT
              ltrim(rtrim(cs.login)) as login
              , ltrim(rtrim(cs.title)) as title
              , ltrim(rtrim(cs.initials)) as initials
              , ltrim(rtrim(cs.family_name)) as family_name
              , '' as ukc
              , ltrim(rtrim(dmc.module_delivery_key)) as module_delivery_key
              , '{$CFG->connect->session_code}' as session_code
              , 'sds_convenor' as role
            FROM d_module_convener AS dmc
              INNER JOIN c_staff AS cs ON dmc.staff = cs.staff
              INNER JOIN m_current_values mcv ON 1=1
              INNER JOIN c_session_dates csd ON csd.session_code = {$codeplus}
            WHERE (
                dmc.staff_function_end_date IS NULL
                OR dmc.staff_function_end_date > CURRENT_TIMESTAMP
                OR (mcv.session_code > {$CFG->connect->session_code}
                AND dmc.staff_function_end_date >= mcv.rollover_date
                AND CURRENT_TIMESTAMP < csd.session_start)
            ) AND cs.login != ''
SQL;

        $this->get_all_sql($sql, $rowcallback);
    }

    /**
     * Grab students out of SDS.
     */
    public function get_all_students($rowcallback) {
        global $CFG;

        $sql = <<<SQL
            SELECT DISTINCT
              ltrim(rtrim(bd.email_address)) as login
              , '' as title
              , ltrim(rtrim(bd.initials)) as initials
              , ltrim(rtrim(bd.family_name)) as family_name
              , ltrim(rtrim(bd.ukc)) as ukc
              , ltrim(rtrim(bm.module_delivery_key)) as module_delivery_key
              , ltrim(rtrim(bm.session_taught)) as session_code
              , ltrim(rtrim(bd.academic)) as status_code
              , 'sds_student' as role
            FROM b_details AS bd
              INNER JOIN b_module AS bm ON bd.ukc = bm.ukc
                AND bd.email_address <> ''
                AND (bm.session_taught = '{$CFG->connect->session_code}') AND (bm.module_registration_status IN ('R','U'))
                AND bd.email_address != ''
SQL;

        $this->get_all_sql($sql, $rowcallback);
    }

    /**
     * Create Table SQL.
     */
    public static function get_create_table_sql($tablename = 'enrolments', $create = 'CREATE TABLE IF NOT EXISTS') {
        global $CFG;

        return <<<SQL
            {$create} {{$tablename}} (
              `id` int(11) NOT NULL,
              `ukc` varchar(255) DEFAULT NULL,
              `login` varchar(255) DEFAULT NULL,
              `title` varchar(255) DEFAULT NULL,
              `initials` varchar(255) DEFAULT NULL,
              `family_name` varchar(255) DEFAULT NULL,
              `session_code` varchar(4) DEFAULT NULL,
              `status_code` varchar(1) DEFAULT '?',
              `module_delivery_key` varchar(36) DEFAULT NULL,
              `role` varchar(255) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE={$CFG->collation}
SQL;
    }

    /**
     * Alter Table SQL.
     */
    public static function get_alter_table_sql($tablename) {
        return <<<SQL
            ALTER TABLE {{$tablename}}
                ADD PRIMARY KEY (`id`),
                ADD UNIQUE KEY index_{$tablename}_on_sdrl (`session_code`,`module_delivery_key`,`role`,`login`),
                ADD KEY index_{$tablename}_on_session_delivery_login (`session_code`,`module_delivery_key`,`login`),
                ADD KEY index_{$tablename}_on_module_delivery_key (`module_delivery_key`),
                ADD KEY index_{$tablename}_on_session_code (`session_code`),
                ADD KEY index_{$tablename}_on_login (`login`),
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT
SQL;
    }

    /**
     * Get a temptable for the sync.
     */
    private function create_temp_table() {
        global $DB;

        $create = static::get_create_table_sql('tmp_connect_enrolments', 'CREATE TEMPORARY TABLE');
        $DB->execute($create);

        $alter = static::get_alter_table_sql('tmp_connect_enrolments');
        $DB->execute($alter);
    }

    /**
     * Destroy the temptable.
     */
    private function destroy_temp_table() {
        global $DB;

        $DB->execute('DROP TEMPORARY TABLE {tmp_connect_enrolments};');
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
            HAVING COUNT(t.role) > 1
        ', array(
            'teacher' => 'sds_teacher',
            'convenor' => 'sds_convenor'
        ));

        foreach ($objs as $obj) {
            $DB->delete_records('tmp_connect_enrolments', array(
                'login' => $obj->login,
                'module_delivery_key' => $obj->module_delivery_key,
                'role' => 'sds_teacher'
            ));
        }
    }

    /**
     * New Roles
     */
    private function sync_new_roles() {
        global $DB;

        echo "  - Migrating new roles\n";

        return $DB->execute('INSERT INTO {connect_role} (name) (
            SELECT e.role
            FROM {tmp_connect_enrolments} e
            LEFT OUTER JOIN {connect_role} cr
                ON cr.name=e.role
            WHERE cr.id IS NULL
            GROUP BY e.role
        )');
    }

    /**
     * Sync New Users
     */
    private function sync_new_users() {
        global $DB;

        echo "  - Migrating new users\n";

        return $DB->execute('INSERT INTO {connect_user} (ukc, login, title, initials, family_name) (
            SELECT COALESCE(e.ukc, \'\'), e.login, COALESCE(e.title, \'\'),
                    COALESCE(e.initials, \'\'), COALESCE(e.family_name, \'\')
            FROM {tmp_connect_enrolments} e
            LEFT OUTER JOIN {connect_user} u
                ON u.login=e.login
            WHERE u.id IS NULL AND e.login IS NOT NULL
            GROUP BY e.login
        )');
    }

    /**
     * Sync Updated Users
     */
    private function sync_updated_users() {
        global $DB;

        echo "  - Migrating updated users\n";

        return $DB->execute('REPLACE INTO {connect_user} (id, ukc, login, title, initials, family_name) (
            SELECT u.id, COALESCE(e.ukc, \'\'), u.login, COALESCE(e.title, \'\'),
                   COALESCE(e.initials, \'\'), COALESCE(e.family_name, \'\')
            FROM {tmp_connect_enrolments} e
            INNER JOIN {connect_user} u
                ON u.login=e.login
            WHERE u.id IS NULL
            GROUP BY e.login
        )');
    }

    /**
     * Deleted Enrolments
     */
    private function sync_deleted_enrolments() {
        global $DB;

        echo "  - Migrating deleted enrolments\n";

        return $DB->execute('
            DELETE ce.* FROM {connect_enrolments} ce

            LEFT OUTER JOIN {connect_course} c ON c.id = ce.courseid
            LEFT OUTER JOIN {connect_user} u ON u.id = ce.userid
            LEFT OUTER JOIN {connect_role} r ON r.id = ce.roleid

            LEFT OUTER JOIN {tmp_connect_enrolments} e
                ON e.module_delivery_key = c.module_delivery_key
                AND e.login = u.login
                AND e.role = r.name
                AND e.status_code = ce.status

            WHERE c.id IS NULL
                OR u.id IS NULL
                OR r.id IS NULL
                OR e.id IS NULL
        ');
    }

    /**
     * New Enrolments
     */
    private function sync_new_enrolments() {
        global $DB;

        echo "  - Migrating new enrolments\n";

        return $DB->execute('INSERT INTO {connect_enrolments} (courseid, userid, roleid, status) (
            SELECT c.id, u.id, r.id, e.status_code
            FROM {tmp_connect_enrolments} e
            INNER JOIN {connect_course} c
                ON c.module_delivery_key=e.module_delivery_key
            INNER JOIN {connect_user} u
                ON u.login=e.login
            INNER JOIN {connect_role} r
                ON r.name=e.role
            LEFT OUTER JOIN {connect_enrolments} ce
                ON ce.courseid=c.id
                AND ce.userid=u.id
                AND ce.roleid=r.id
                AND ce.status=e.status_code
            WHERE ce.id IS NULL
        )');
    }

    /**
     * Map old roles to connect
     */
    private function map_roles() {
        global $DB;

        echo "  - Mapping new roles\n";

        $DB->execute('
            UPDATE {connect_role} cr
            INNER JOIN {role} r
                ON r.shortname = cr.name
            SET cr.mid=r.id
            WHERE cr.mid = 0
        ');
    }

    /**
     * Map old users to connect
     */
    private function map_users() {
        global $DB;

        echo "  - Mapping new users\n";

        $DB->execute('
            UPDATE {connect_user} cu
            INNER JOIN {user} u
                ON u.username = cu.login
            SET cu.mid=u.id
            WHERE cu.mid = 0
        ');
    }

    /**
     * Get some sync stats.
     */
    public function get_stats() {
        global $CFG, $DB;

        $convenors = $DB->count_records('tmp_connect_enrolments', array(
            'role' => 'sds_convenor'
        ));

        $teachers = $DB->count_records('tmp_connect_enrolments', array(
            'role' => 'sds_teacher'
        ));

        $students = $DB->count_records('tmp_connect_enrolments', array(
            'role' => 'sds_student'
        ));

        $total = ($convenors + $teachers + $students);

        echo "  - $total enrolments found.\n";
        echo "    - $convenors convenors\n";
        echo "    - $teachers teachers\n";
        echo "    - $students students\n";

        return array($total, $convenors, $teachers, $students);
    }

    /**
     * Sync enrolments with Moodle.
     */
    public function execute() {
        global $CFG, $DB;

        echo "Synching Enrolments...\n";

        // Create a temp table.
        $this->create_temp_table();

        // Load data into the temp table.
        echo "  - Loading teacher data from SDS...\n";
        $this->get_all_teachers(function($row) {
            global $DB;
            $DB->insert_records('tmp_connect_enrolments', $row);
        });

        echo "  - Loading convenor data from SDS...\n";
        $this->get_all_convenors(function($row) {
            global $DB;
            $DB->insert_records('tmp_connect_enrolments', $row);
        });

        echo "  - Cleaning duplicates...\n";
        $this->clean_tmp();

        echo "  - Loading student data from SDS...\n";
        $this->get_all_students(function($row) {
            global $DB;
            $DB->insert_records('tmp_connect_enrolments', $row);
        });

        list($total, $convenors, $teachers, $students) = $this->get_stats();

        // Sync.
        if ($convenors > 50 && $teachers > 50 && $students > 50) {
            echo "  - Importing roles & users...\n";
            $this->sync_new_roles();
            $this->map_roles();
            $this->sync_updated_users();
            $this->sync_new_users();
            $this->map_users();
            echo "  - Importing enrolments...\n";
            $this->sync_deleted_enrolments();
            $this->sync_new_enrolments();
        }

        // Drop the temp table.
        $this->destroy_temp_table();
    }
}
