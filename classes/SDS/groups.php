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
 * Moodle-SDS Sync Stack
 *
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect\SDS;

/**
 * Grabs all groups out of SDS.
 */
class groups {
    /**
     * Grab out of SDS.
     */
    public function get_all() {
        global $CFG, $SDSDB;

        $sql = <<<SQL
            SELECT DISTINCT
              ltrim(rtrim(cg.group_id)) AS group_id,
              ltrim(rtrim(cg.group_desc)) AS group_desc,
              ltrim(rtrim(dgm.module_delivery_key)) AS module_delivery_key
            FROM d_group_module AS dgm
              INNER JOIN c_groups AS cg ON cg.parent_group = dgm.group_id
              LEFT JOIN l_ukc_group AS lug on lug.group_id = cg.group_id
              LEFT JOIN b_details AS bd on bd.ukc = lug.ukc
            WHERE (dgm.session_code = :sesscode)
              AND (cg.group_type = 'S')
              AND bd.email_address != ''
SQL;

        return $SDSDB->get_records_sql($sql, array(
            'sesscode' => $CFG->connect->session_code
        ));
    }

    /**
     * Get a temptable for the sync.
     */
    private function get_temp_table() {
        global $CFG;

        require_once($CFG->libdir . '/ddllib.php');

        $table = new \xmldb_table('tmp_connect_groups');
        $table->add_field('group_id', XMLDB_TYPE_INTEGER, '18', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('group_desc', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('module_delivery_key', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('group_id'));

        return $table;
    }

    /**
     * Deleted Groups
     */
    private function sync_deleted_groups() {
        global $DB;

        echo "  - Migrating deleted groups\n";

        return $DB->execute("
            DELETE cg.* FROM {connect_group} cg
            LEFT OUTER JOIN {connect_course} c
                ON c.id = cg.courseid
            LEFT OUTER JOIN {tmp_connect_groups} tmp
                ON tmp.module_delivery_key = c.module_delivery_key
                    AND cg.id = tmp.group_id
            WHERE c.id IS NULL OR tmp.group_id IS NULL
        ");
    }

    /**
     * Updated Groups
     */
    private function sync_updated_groups() {
        global $DB;

        echo "  - Migrating updated groups\n";

        return $DB->execute("
            REPLACE INTO {connect_group} (id, courseid, name, mid)
            (
                SELECT g.group_id, c.id, g.group_desc, cg.mid
                FROM {tmp_connect_groups} g
                INNER JOIN {connect_course} c ON c.module_delivery_key=g.module_delivery_key
                INNER JOIN {connect_group} cg ON cg.id=g.group_id
                WHERE g.group_desc <> cg.name
                GROUP BY g.group_id
            )
        ");
    }

    /**
     * New Groups
     */
    private function sync_new_groups() {
        global $DB;

        echo "  - Migrating new groups\n";

        return $DB->execute("
            INSERT INTO {connect_group} (id, courseid, name, mid)
            (
                SELECT g.group_id, c.id, g.group_desc, 0
                FROM {tmp_connect_groups} g
                INNER JOIN {connect_course} c ON c.module_delivery_key=g.module_delivery_key
                LEFT OUTER JOIN {connect_group} cg ON cg.id=g.group_id
                WHERE cg.id IS NULL
                GROUP BY g.group_id
            )
        ");
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
    public function sync() {
        global $CFG, $DB;

        // Create a temp table.
        $table = $this->get_temp_table();
        $dbman = $DB->get_manager();
        $dbman->create_temp_table($table);

        // Load data into the temp table.
        $DB->insert_records('tmp_connect_groups', $this->get_all());
        $stats = $this->get_stats();

        // Move data over.
        if ($stats > 50) {
            $this->sync_deleted_groups();
            $this->sync_updated_groups();
            $this->sync_new_groups();
        }

        // Drop the temp table.
        $dbman->drop_table($table);
    }
}