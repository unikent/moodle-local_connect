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
 * Grabs all timetabling information out of SDS.
 */
class timetabling extends \core\task\adhoc_task
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
    public function get_weeks($rowcallback) {
        global $CFG;

        $sql = <<<SQL
            SELECT DISTINCT
              ltrim(rtrim(cast(cswb.week_beginning as varchar))) as week_beginning,
              ltrim(rtrim(cast(cswb.week_beginning_date as varchar))) as week_beginning_date,
              ltrim(rtrim(cast(cswb.week_number as varchar))) as week_number
            FROM c_session_week_beginning cswb
            WHERE cswb.session_code = {$CFG->connect->session_code}
SQL;

        $this->get_all_sql($sql, $rowcallback);
    }

    /**
     * Create Table SQL.
     */
    public static function get_create_table_sql($tablename = 'weeks', $create = 'CREATE TABLE IF NOT EXISTS') {
        global $CFG;

        return <<<SQL
            {$create} {{$tablename}} (
              `id` int(11) NOT NULL,
              `week_beginning` varchar(255) DEFAULT NULL,
              `week_beginning_date` varchar(255) DEFAULT NULL,
              `week_number` varchar(255) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE={$CFG->collation};
SQL;
    }

    /**
     * Alter Table SQL.
     */
    public static function get_alter_table_sql($tablename) {
        return <<<SQL
            ALTER TABLE {{$tablename}}
                ADD PRIMARY KEY (`id`),
                ADD KEY index_{$tablename}_on_week_beginning (`week_beginning`),
                ADD KEY index_{$tablename}_on_week_beginning_date (`week_beginning_date`),
                ADD KEY index_{$tablename}_on_week_number (`week_number`),
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
SQL;
    }

    /**
     * Get a temptable for the sync.
     */
    private function create_temp_table() {
        global $DB;

        $create = static::get_create_table_sql('tmp_connect_weeks', 'CREATE TEMPORARY TABLE');
        $DB->execute($create);

        $alter = static::get_alter_table_sql('tmp_connect_weeks');
        $DB->execute($alter);
    }

    /**
     * Destroy the temptable.
     */
    private function destroy_temp_table() {
        global $DB;

        $DB->execute('DROP TEMPORARY TABLE {tmp_connect_weeks};');
    }

    /**
     * Port new weeks.
     */
    private function sync_new_weeks() {
        global $DB;

        echo "  - Migrating new weeks\n";

        return $DB->execute('
            INSERT INTO {connect_weeks} (week_beginning, week_beginning_date, week_number)
            (
                SELECT cwb.week_beginning, STR_TO_DATE(cwb.week_beginning_date, \'%b %e %Y %H:%iAM\'), cwb.week_number
                FROM {tmp_connect_weeks} cwb
                LEFT OUTER JOIN {connect_weeks} cw ON cw.week_beginning=cwb.week_beginning
                WHERE cw.id IS NULL
            )
        ');
    }

    /**
     * Port updated weeks.
     */
    private function sync_updated_weeks() {
        global $DB;

        echo "  - Migrating updated weeks\n";

        return $DB->execute('
            REPLACE INTO {connect_weeks} (id, week_beginning, week_beginning_date, week_number)
            (
                SELECT cw.id, cwb.week_beginning, STR_TO_DATE(cwb.week_beginning_date, \'%b %e %Y %H:%iAM\'), cwb.week_number
                FROM {tmp_connect_weeks} cwb
                INNER JOIN {connect_weeks} cw ON cw.week_beginning=cwb.week_beginning
                WHERE
                    cw.week_beginning_date <> STR_TO_DATE(cwb.week_beginning_date, \'%b %e %Y %H:%iAM\')
                    OR cw.week_number <> cwb.week_number
            )
        ');
    }

    /**
     * Sync weeks with Moodle.
     */
    public function execute() {
        global $DB;

        echo "Synching Timetabling...\n";

        // Create a temp table.
        $this->create_temp_table();

        // Load data into the temp table.
        $this->get_weeks(function($row) {
            global $DB;
            $DB->insert_records('tmp_connect_weeks', $row);
        });

        // Move data over.
        $this->sync_updated_weeks();
        $this->sync_new_weeks();

        // Drop the temp table.
        $this->destroy_temp_table();
    }
}
