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
 * Grabs all group enrolments out of SDS.
 */
class group_enrolments extends \core\task\adhoc_task
{
    use sql_helper;

    /**
     * Returns the component name.
     */
    public function get_component() {
        return 'local_connect';
    }

    /**
     * Grab out of SDS.
     */
    public function get_all($rowcallback) {
        global $CFG;

        $sql = <<<SQL
            SELECT DISTINCT
              ltrim(rtrim(cg.group_id)) AS group_id,
              ltrim(rtrim(bd.email_address)) as login
            FROM d_group_module AS dgm
              INNER JOIN c_groups AS cg ON cg.parent_group = dgm.group_id
              LEFT JOIN l_ukc_group AS lug on lug.group_id = cg.group_id
              LEFT JOIN b_details AS bd on bd.ukc = lug.ukc
            WHERE (dgm.session_code = {$CFG->connect->session_code})
              AND (cg.group_type = 'S')
              AND bd.email_address != ''
SQL;

        $this->get_all_sql($sql, $rowcallback);
    }

    /**
     * Create Table SQL.
     */
    public static function get_create_table_sql($tablename = 'group_enrolments', $create = 'CREATE TABLE IF NOT EXISTS') {
        global $CFG;

        $collation = $CFG->dboptions['dbcollation'];
        return <<<SQL
            {$create} {{$tablename}} (
              `id` int(11) NOT NULL,
              `group_id` varchar(255) DEFAULT NULL,
              `login` varchar(255) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE={$collation}
SQL;
    }

    /**
     * Alter Table SQL.
     */
    public static function get_alter_table_sql($tablename) {
        return <<<SQL
            ALTER TABLE {{$tablename}}
                ADD PRIMARY KEY (`id`),
                ADD KEY index_{$tablename}_on_group_id (`group_id`),
                ADD KEY index_{$tablename}_on_login (`login`),
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT
SQL;
    }

    /**
     * Get a temptable for the sync.
     */
    private function create_temp_table() {
        global $DB;

        $create = static::get_create_table_sql('tmp_connect_group_enrolments', 'CREATE TEMPORARY TABLE');
        $DB->execute($create);

        $alter = static::get_alter_table_sql('tmp_connect_group_enrolments');
        $DB->execute($alter);
    }

    /**
     * Destroy the temptable.
     */
    private function destroy_temp_table() {
        global $DB;

        $DB->execute('DROP TEMPORARY TABLE {tmp_connect_group_enrolments}');
    }

    /**
     * Deleted Group Enrolments
     */
    private function sync_deleted_group_enrolments() {
        global $DB;

        echo "  - Migrating deleted group enrolments\n";

        return $DB->execute('
            DELETE cge.* FROM {connect_group_enrolments} cge
            LEFT OUTER JOIN {connect_user} u
                ON u.id=cge.userid
            LEFT OUTER JOIN {tmp_connect_group_enrolments} tcge
                ON tcge.group_id = cge.groupid AND tcge.login = u.login
            WHERE u.id IS NULL
              OR tcge.id IS NULL
        ');
    }

    /**
     * New Group Enrolments
     */
    private function sync_new_group_enrolments() {
        global $DB;

        echo "  - Migrating new group enrolments\n";

        return $DB->execute('
            INSERT INTO {connect_group_enrolments} (groupid, userid)
            (
                SELECT ge.group_id, u.id
                FROM {tmp_connect_group_enrolments} ge
                INNER JOIN {connect_user} u ON u.login = ge.login
                LEFT OUTER JOIN {connect_group_enrolments} cge ON cge.groupid=ge.group_id AND cge.userid=u.id
                WHERE cge.id IS NULL
            )
        ');
    }

    /**
     * Get some sync stats.
     */
    public function get_stats() {
        global $DB;

        $total = $DB->count_records('tmp_connect_group_enrolments');
        echo "  - $total group enrolments found.\n";
        return $total;
    }

    /**
     * Sync group enrolments with Moodle.
     */
    public function execute() {
        global $CFG, $DB;

        echo "Synching Group Enrolments...\n";

        // Create a temp table.
        $this->create_temp_table();

        // Load data into the temp table.
        $this->get_all(function($row) {
            global $DB;

            $DB->insert_records('tmp_connect_group_enrolments', $row);
        });

        $stats = $this->get_stats();

        // Move data over.
        if ($stats > 50) {
            $this->sync_deleted_group_enrolments();
            $this->sync_new_group_enrolments();
        }

        // Drop the temp table.
        $this->destroy_temp_table();
    }
}
