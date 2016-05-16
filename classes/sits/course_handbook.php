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

namespace local_connect\sits;

defined('MOODLE_INTERNAL') || die();

/**
 * Grabs all course handbook data out of SITS.
 */
class course_handbook extends \core\task\adhoc_task
{
    use sql_helper;

    /**
     * Returns the component name.
     */
    public function get_component() {
        return 'local_connect';
    }

    /**
     * Grab out of SITS.
     *
     * @todo moodle_alternative_code
     */
    public function get_all($rowcallback) {
        global $CFG;

        $sql = <<<SQL
            SELECT * FROM vw_Module_Handbook_Details WHERE academic_year = {$CFG->connect->session_code}
SQL;

        $this->get_all_sql($sql, $rowcallback);
    }

    /**
     * Create Table SQL.
     */
    public static function get_create_table_sql($tablename = 'connect_course_handbooks', $create = 'CREATE TABLE IF NOT EXISTS') {
        global $CFG;

        $collation = $CFG->dboptions['dbcollation'];
        return <<<SQL
            {$create} {{$tablename}} (
                module_code VARCHAR(255) NOT NULL,
                synopsis LONGTEXT,
                publicationssynopsis LONGTEXT,
                contacthours LONGTEXT,
                learningoutcome LONGTEXT,
                methodofassessment LONGTEXT,
                preliminaryreading LONGTEXT,
                updateddate BIGINT(11),
                availability LONGTEXT,
                cost LONGTEXT,
                prerequisites LONGTEXT,
                progression LONGTEXT,
                restrictions LONGTEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE={$collation}
SQL;
    }

    /**
     * Alter Table SQL.
     */
    public static function get_alter_table_sql($tablename) {
        return <<<SQL
            ALTER TABLE {{$tablename}}
                ADD PRIMARY KEY (`module_code`)
SQL;
    }

    /**
     * Get a temptable for the sync.
     */
    private function create_temp_table() {
        global $DB;

        $create = static::get_create_table_sql('tmp_connect_course_handbook', 'CREATE TEMPORARY TABLE');
        $DB->execute($create);

        $alter = static::get_alter_table_sql('tmp_connect_course_handbook');
        $DB->execute($alter);
    }

    /**
     * Destroy the temptable.
     */
    private function destroy_temp_table() {
        global $DB;

        $DB->execute('DROP TEMPORARY TABLE {tmp_connect_course_handbook}');
    }

    /**
     * Deleted Groups
     */
    private function sync_deleted_handbooks() {
        global $DB;

        echo "  - Migrating deleted handbooks\n";

        return $DB->execute('
            DELETE cch.* FROM {connect_course_handbook} cch
            LEFT OUTER JOIN {tmp_connect_course_handbook} tmp
                ON tmp.module_code = cch.module_code
            WHERE tmp.module_code IS NULL
        ');
    }

    /**
     * Updated Groups
     */
    private function sync_updated_handbooks() {
        global $DB;

        echo "  - Migrating updated handbooks\n";

        return $DB->execute('
            REPLACE INTO {connect_course_handbook} (id,module_code,synopsis,publicationssynopsis,contacthours,
                                                    learningoutcome,methodofassessment,preliminaryreading,updateddate,
                                                    availability,cost,prerequisites,progression,restrictions)
            (
                SELECT cch.id, ccht.*
                FROM {tmp_connect_course_handbook} ccht
                INNER JOIN {connect_course_handbook} cch
                    ON cch.module_code=ccht.module_code
            )
        ');
    }

    /**
     * New Groups
     */
    private function sync_new_handbooks() {
        global $DB;

        echo "  - Migrating new handbooks\n";

        return $DB->execute('
            INSERT INTO {connect_course_handbook} (module_code,synopsis,publicationssynopsis,contacthours,
                                                    learningoutcome,methodofassessment,preliminaryreading,updateddate,
                                                    availability,cost,prerequisites,progression,restrictions)
            (
                SELECT ccht.*
                FROM {tmp_connect_course_handbook} ccht
                LEFT OUTER JOIN {connect_course_handbook} cch
                    ON cch.module_code=ccht.module_code
                WHERE cch.id IS NULL
            )
        ');
    }

    /**
     * Get some sync stats.
     */
    public function get_stats() {
        global $DB;

        $total = $DB->count_records('tmp_connect_course_handbook');
        echo "  - $total handbooks found.\n";
        return $total;
    }

    /**
     * Sync handbooks with Moodle.
     */
    public function execute() {
        global $DB;

        echo "Synching module handbooks...\n";

        // Create a temp table.
        $this->create_temp_table();

        // Load data into the temp table.
        $this->get_all(function($rows) {
            global $DB;

            $tmp = array();
            foreach ($rows as $row) {
                $row->updateddate = strtotime($row->updateddate);
                $row->module_code = $row->sds_module_code;
                $tmp[] = $row;
            }

            $DB->insert_records('tmp_connect_course_handbook', $tmp);
        });
        $stats = $this->get_stats();

        // Move data over.
        if ($stats > 50) {
            $this->sync_deleted_handbooks();
            $this->sync_updated_handbooks();
            $this->sync_new_handbooks();
        }

        // Drop the temp table.
        $this->destroy_temp_table();
    }
}
