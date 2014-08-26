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

global $CFG;
require_once($CFG->libdir . '/ddllib.php');

/**
 * Grabs all group enrolments out of SDS.
 */
class group_enrolments {
    /**
     * Grab out of SDS.
     */
    private function get_all() {
        global $CFG, $SDSDB;

        db::obtain();

        $sql = <<<SQL
            SELECT DISTINCT
              ltrim(rtrim(cg.group_id)) + '|' + ltrim(rtrim(bd.email_address)) AS chksum,
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

        return $SDSDB->get_records_sql($sql);
    }

    /**
     * Get a temptable for the sync.
     */
    private function get_temp_table() {
        global $CFG;

        require_once($CFG->libdir . '/ddllib.php');

        $table = new \xmldb_table('tmp_connect_group_enrolments');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('group_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('login', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        return $table;
    }

    /**
     * Deleted Group Enrolments
     */
    private function sync_deleted_group_enrolments() {
        global $DB;

        echo "  - Migrating updated group enrolments\n";

        return $DB->execute("
            REPLACE INTO {connect_group_enrolments} (id, groupid, userid, deleted)
            (
                SELECT cge.id, cge.groupid, cge.id, 1
                FROM {connect_group_enrolments} cge
                INNER JOIN {connect_user} u ON u.id=cge.userid
                LEFT OUTER JOIN {tmp_connect_group_enrolments} tcge
                    ON tcge.group_id = cge.groupid AND tcge.login = u.login
                WHERE tcge.id IS NULL
            )
        ");
    }

    /**
     * New Group Enrolments
     */
    private function sync_new_group_enrolments() {
        global $DB;

        echo "  - Migrating new group enrolments\n";

        return $DB->execute("
            INSERT INTO {connect_group_enrolments} (groupid, userid, deleted)
            (
                SELECT ge.group_id, u.id, 0
                FROM {tmp_connect_group_enrolments} ge
                INNER JOIN {connect_user} u ON u.login = ge.login
                LEFT OUTER JOIN {connect_group_enrolments} cge ON cge.groupid=ge.group_id AND cge.userid=u.id
                WHERE cge.id IS NULL
            )
        ");
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
        $DB->insert_records('tmp_connect_group_enrolments', $this->get_all());

        // Move data over.
        $this->sync_deleted_group_enrolments();
        $this->sync_new_group_enrolments();

        // Drop the temp table.
        $dbman->drop_table($table);
    }
}