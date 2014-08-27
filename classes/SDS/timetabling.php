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
 * Grabs all timetabling information out of SDS.
 */
class timetabling {
    /**
     * Grab out of SDS.
     */
    private function get_week_sessions() {
        global $CFG, $SDSDB;

        $sql = <<<SQL
            SELECT DISTINCT
              ltrim(rtrim(cast(cswb.session_code as varchar))) + '|' + ltrim(rtrim(cast(cswb.week_beginning as varchar))) as chksum,
              ltrim(rtrim(cast(cswb.week_beginning as varchar))) as week_beginning,
              ltrim(rtrim(cast(cswb.week_beginning_date as varchar))) as week_beginning_date,
              ltrim(rtrim(cast(cswb.week_number as varchar))) as week_number
            FROM c_session_week_beginning cswb
            WHERE cswb.session_code = :sesscode
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

        $table = new \xmldb_table('tmp_connect_weeeks');
        $table->add_field('week_beginning', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('week_beginning_date', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('week_number', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        return $table;
    }

    /**
     * Port new weeks.
     */
    private function sync_new_weeks() {
        global $DB;

        echo "  - Migrating new weeks\n";

        return $DB->execute("
            INSERT INTO {connect_weeks} (week_beginning, week_beginning_date, week_number)
            (
                SELECT cwb.week_beginning, STR_TO_DATE(cwb.week_beginning_date, '%b %e %Y %H:%iAM'), cwb.week_number
                FROM {tmp_connect_weeeks} cwb
                LEFT OUTER JOIN {connect_weeks} cw ON cw.week_beginning=cwb.week_beginning
                WHERE cw.id IS NULL
            )
        ");
    }

    /**
     * Port updated weeks.
     */
    private function sync_updated_weeks() {
        global $DB;

        echo "  - Migrating updated weeks\n";

        return $DB->execute("
            REPLACE INTO {connect_weeks} (id, week_beginning, week_beginning_date, week_number)
            (
                SELECT cw.id, cwb.week_beginning, STR_TO_DATE(cwb.week_beginning_date, '%b %e %Y %H:%iAM'), cwb.week_number
                FROM {tmp_connect_weeeks} cwb
                INNER JOIN {connect_weeks} cw ON cw.week_beginning=cwb.week_beginning
                WHERE
                    cw.week_beginning_date <> STR_TO_DATE(cwb.week_beginning_date, '%b %e %Y %H:%iAM')
                    OR cw.week_number <> cwb.week_number
            )
        ");
    }

    /**
     * Sync weeks with Moodle.
     */
    public function sync() {
        global $CFG, $DB;

        // Create a temp table.
        $table = $this->get_temp_table();
        $dbman = $DB->get_manager();
        $dbman->create_temp_table($table);

        // Load data into the temp table.
        $DB->insert_records('tmp_connect_weeeks', $this->get_all());

        // Move data over.
        $this->sync_updated_weeks();
        $this->sync_new_weeks();

        // Drop the temp table.
        $dbman->drop_table($table);
    }
}