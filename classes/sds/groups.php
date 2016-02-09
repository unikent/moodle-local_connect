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
 * Grabs all groups out of SDS.
 */
class groups extends \core\task\adhoc_task
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
              ltrim(rtrim(cg.group_desc)) AS group_desc,
              ltrim(rtrim(dgm.module_delivery_key)) AS module_delivery_key
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
    public static function get_create_table_sql($tablename = 'groups', $create = 'CREATE TABLE IF NOT EXISTS') {
        global $CFG;

        return <<<SQL
            {$create} {{$tablename}} (
              `group_id` int(11) NOT NULL,
              `group_desc` varchar(255) DEFAULT NULL,
              `module_delivery_key` varchar(36) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE={$CFG->collation}
SQL;
    }

    /**
     * Alter Table SQL.
     */
    public static function get_alter_table_sql($tablename) {
        return <<<SQL
            ALTER TABLE {{$tablename}}
                ADD PRIMARY KEY (`group_id`),
                ADD KEY index_{$tablename}_on_group_desc (`group_desc`),
                ADD KEY index_{$tablename}_on_module_delivery_key (`module_delivery_key`)
SQL;
    }

    /**
     * Get a temptable for the sync.
     */
    private function create_temp_table() {
        global $DB;

        $create = static::get_create_table_sql('tmp_connect_groups', 'CREATE TEMPORARY TABLE');
        $DB->execute($create);

        $alter = static::get_alter_table_sql('tmp_connect_groups');
        $DB->execute($alter);
    }

    /**
     * Destroy the temptable.
     */
    private function destroy_temp_table() {
        global $DB;

        $DB->execute('DROP TEMPORARY TABLE {tmp_connect_groups};');
    }

    /**
     * Deleted Groups
     */
    private function sync_deleted_groups() {
        global $DB;

        echo "  - Migrating deleted groups\n";

        return $DB->execute('
            DELETE cg.* FROM {connect_group} cg
            LEFT OUTER JOIN {connect_course} c
                ON c.id = cg.courseid
            LEFT OUTER JOIN {tmp_connect_groups} tmp
                ON tmp.module_delivery_key = c.module_delivery_key
                    AND cg.id = tmp.group_id
            WHERE c.id IS NULL OR tmp.group_id IS NULL
        ');
    }

    /**
     * Updated Groups
     */
    private function sync_updated_groups() {
        global $DB;

        echo "  - Migrating updated groups\n";

        return $DB->execute('
            REPLACE INTO {connect_group} (id, courseid, name, mid)
            (
                SELECT g.group_id, c.id, g.group_desc, cg.mid
                FROM {tmp_connect_groups} g
                INNER JOIN {connect_course} c ON c.module_delivery_key=g.module_delivery_key
                INNER JOIN {connect_group} cg ON cg.id=g.group_id
                WHERE g.group_desc <> cg.name
                GROUP BY g.group_id
            )
        ');
    }

    /**
     * New Groups
     */
    private function sync_new_groups() {
        global $DB;

        echo "  - Migrating new groups\n";

        return $DB->execute('
            INSERT INTO {connect_group} (id, courseid, name, mid)
            (
                SELECT g.group_id, c.id, g.group_desc, 0
                FROM {tmp_connect_groups} g
                INNER JOIN {connect_course} c ON c.module_delivery_key=g.module_delivery_key
                LEFT OUTER JOIN {connect_group} cg ON cg.id=g.group_id
                WHERE cg.id IS NULL
                GROUP BY g.group_id
            )
        ');
    }

    /**
     * Get some sync stats.
     */
    public function get_stats() {
        global $DB;

        $total = $DB->count_records('tmp_connect_groups');
        echo "  - $total groups found.\n";
        return $total;
    }

    /**
     * Sync groups with Moodle.
     */
    public function execute() {
        global $DB;

        echo "Synching Groups...\n";

        // Create a temp table.
        $this->create_temp_table();

        // Load data into the temp table.
        $this->get_all(function($row) {
            global $DB;
            $DB->insert_records('tmp_connect_groups', $row);
        });
        $stats = $this->get_stats();

        // Move data over.
        if ($stats > 50) {
            $this->sync_deleted_groups();
            $this->sync_updated_groups();
            $this->sync_new_groups();
        }

        // Drop the temp table.
        $this->destroy_temp_table();
    }
}
