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
 * Grabs all courses out of SDS.
 */
class courses extends \core\task\adhoc_task
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
              ltrim(rtrim(dmd.module_delivery_key)) as module_delivery_key,
              ltrim(rtrim(dmds.session_code)) as session_code,
              ltrim(rtrim(dmd.delivery_department)) as delivery_department,
              ltrim(rtrim(cmd.module_version)) as module_version,
              ltrim(rtrim(cc.campus)) as campus,
              ltrim(rtrim(cc.campus_desc)) as campus_desc,
              ltrim(rtrim(dmd.module_week_beginning)) as module_week_beginning,
              swb.week_beginning_date as week_beginning_date,
              ltrim(rtrim(cmd.module_length)) as module_length,
              ltrim(rtrim(cmd.module_title)) as module_title,
              ltrim(rtrim(cmd.module_code)) as module_code,
              ltrim(rtrim(cmd.credit_level)) as credit_level
            FROM d_module_delivery dmd
              INNER JOIN d_module_delivery_session AS dmds
                ON dmds.module_delivery_key = dmd.module_delivery_key
                AND dmds.module_status = 'ACTIVE'
                AND dmds.session_code = {$CFG->connect->session_code}
              INNER JOIN c_campus AS cc ON dmd.delivery_campus = cc.campus
              INNER JOIN c_module_details AS cmd
                  ON cmd.module_code = dmd.module_code AND cmd.module_version = dmd.module_version
              INNER JOIN c_session_week_beginning AS swb
                  ON swb.week_beginning = dmd.module_week_beginning
                  AND swb.session_code = {$CFG->connect->session_code}
            WHERE dmd.delivery_faculty IN ('A','H','S','U')
SQL;

        $this->get_all_sql($sql, $rowcallback);
    }

    /**
     * Create Table SQL.
     */
    public static function get_create_table_sql($tablename = 'courses', $create = 'CREATE TABLE IF NOT EXISTS') {
        global $CFG;

        $collation = $CFG->dboptions['dbcollation'];
        return <<<SQL
            {$create} {{$tablename}} (
              `id` int(11) NOT NULL,
              `module_delivery_key` varchar(36) DEFAULT NULL,
              `session_code` varchar(4) DEFAULT NULL,
              `delivery_department` varchar(4) DEFAULT NULL,
              `campus` varchar(255) DEFAULT NULL,
              `module_version` varchar(4) DEFAULT NULL,
              `campus_desc` varchar(255) DEFAULT NULL,
              `module_week_beginning` varchar(4) DEFAULT NULL,
              `module_length` varchar(4) DEFAULT NULL,
              `module_title` varchar(255) DEFAULT NULL,
              `module_code` varchar(255) DEFAULT NULL,
              `module_code_sds` varchar(255) DEFAULT NULL,
              `credit_level` varchar(255) DEFAULT NULL,
              `moodle_id` int(11) DEFAULT NULL,
              `sink_deleted` tinyint(1) DEFAULT '0',
              `state` int(11) DEFAULT '0',
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              `week_beginning_date` datetime DEFAULT NULL,
              `category_id` int(11) DEFAULT NULL,
              `parent_id` varchar(36) DEFAULT NULL,
              `student_count` int(11) DEFAULT '0',
              `teacher_count` int(11) DEFAULT '0',
              `convenor_count` int(11) DEFAULT '0',
              `link` tinyint(1) DEFAULT '0',
              `interface` tinyint(1) DEFAULT '1',
              `json_cache` text,
              `primary_child` varchar(36) DEFAULT NULL,
              `last_checked` datetime DEFAULT NULL
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
                ADD KEY index_{$tablename}_on_module_delivery_key (`module_delivery_key`),
                ADD KEY index_{$tablename}_on_session_code (`session_code`),
                ADD KEY index_{$tablename}_on_state (`state`),
                ADD KEY index_{$tablename}_on_parent_id (`parent_id`),
                ADD KEY index_{$tablename}_on_session_delivery (`session_code`,`module_delivery_key`),
                ADD KEY index_{$tablename}_on_primary_child (`primary_child`),
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT
SQL;
    }

    /**
     * Get a temptable for the sync.
     */
    private function create_temp_table() {
        global $DB;

        $create = static::get_create_table_sql('tmp_connect_courses', 'CREATE TEMPORARY TABLE');
        $DB->execute($create);

        $alter = static::get_alter_table_sql('tmp_connect_courses');
        $DB->execute($alter);
    }

    /**
     * Destroy the temptable.
     */
    private function destroy_temp_table() {
        global $DB;

        $DB->execute('DROP TEMPORARY TABLE {tmp_connect_courses}');
    }

    /**
     * New Campuses
     */
    protected function sync_new_campus() {
        global $DB;

        echo "  - Migrating new campus\n";

        $sql = '
            INSERT INTO {connect_campus} (id, shortname, name)
            (
                SELECT c.campus, c.campus_desc, c.campus_desc
                FROM {tmp_connect_courses} c
                LEFT OUTER JOIN {connect_campus} cc
                    ON cc.id = c.campus
                WHERE cc.id IS NULL
                GROUP BY c.campus
            )
        ';

        return $DB->execute($sql);
    }

    /**
     * Deleted Courses
     */
    private function sync_deleted_courses() {
        global $DB;

        echo "  - Migrating deleted courses\n";

        return $DB->execute('
            UPDATE {connect_course} cc
            LEFT OUTER JOIN {tmp_connect_courses} tmp
                ON cc.module_delivery_key = tmp.module_delivery_key
                    AND cc.module_version = tmp.module_version
            SET cc.deleted=1
            WHERE tmp.id IS NULL
        ');
    }

    /**
     * Updated Courses
     */
    private function sync_updated_courses() {
        global $DB;

        echo "  - Migrating updated courses\n";

        return $DB->execute('
            REPLACE INTO {connect_course} (
                id,module_delivery_key,session_code,module_version,credit_level,campusid,module_week_beginning,module_length,
                week_beginning_date,module_title,module_code,module_code_sds,category,department,mid,interface,deleted)
            (
                SELECT cc.id, c.module_delivery_key,c.session_code,COALESCE(c.module_version,1),c.credit_level,
                       c.campus as campusid,c.module_week_beginning,c.module_length,c.week_beginning_date,
                       c.module_title,c.module_code,c.module_code_sds,cc.category,
                       COALESCE(c.delivery_department,0),COALESCE(cc.mid,0),c.interface,0
                FROM {tmp_connect_courses} c
                INNER JOIN {connect_course} cc
                    ON cc.module_delivery_key = c.module_delivery_key AND cc.module_version = c.module_version
                GROUP BY c.module_delivery_key, c.module_version
            )
        ');
    }

    /**
     * New Courses
     */
    private function sync_new_courses() {
        global $DB;

        echo "  - Migrating new courses\n";

        return $DB->execute('
            INSERT INTO {connect_course} (
                module_delivery_key,session_code,module_version,credit_level,campusid,module_week_beginning,module_length,
                week_beginning_date,module_title,module_code,module_code_sds,category,department,mid,interface,deleted)
            (
                SELECT c.module_delivery_key,c.session_code,COALESCE(c.module_version,1),c.credit_level,
                       c.campus as campusid,c.module_week_beginning,c.module_length,c.week_beginning_date,
                       c.module_title,c.module_code,c.module_code_sds,0,c.delivery_department,0,c.interface,0
                FROM {tmp_connect_courses} c
                LEFT OUTER JOIN {connect_course} cc
                    ON cc.module_delivery_key = c.module_delivery_key AND cc.module_version = c.module_version
                WHERE cc.id IS NULL
                GROUP BY c.module_delivery_key, c.module_version
            )
        ');
    }

    /**
     * Maps courses, if a new course is just a version bump
     * attach it.
     */
    private function map_courses() {
        global $DB;

        echo "  - Mapping new courses\n";

        $sql = <<<SQL
            SELECT c.id, cc.id AS primaryid, cc.mid
            FROM {connect_course} c
            INNER JOIN {connect_course} cc
                ON cc.module_code = c.module_code
                AND cc.module_length = c.module_length
                AND cc.module_week_beginning = c.module_week_beginning
                AND cc.campusid = c.campusid
                AND cc.module_version < c.module_version
            WHERE cc.mid > 0 AND (c.mid = 0 OR c.mid IS NULL)
            GROUP BY c.id
SQL;

        $results = $DB->get_records_sql($sql);
        foreach ($results as $result) {
            // We need to map this.
            echo "    - Mapping {$result->primaryid} to {$result->id}..\n";
            $DB->execute('UPDATE {connect_course} SET mid=' . $result->mid . ' WHERE id=' . $result->id);
        }

        return true;
    }

    /**
     * Get some sync stats.
     */
    public function get_stats() {
        global $DB;

        $total = $DB->count_records('tmp_connect_courses');
        echo "  - $total courses found.\n";
        return $total;
    }

    /**
     * Cleanup a course.
     */
    protected function clean_row($row) {
        $row = (object)$row;

        $row->interface = \local_connect\core::INTERFACE_SDS;
        $row->week_beginning_date = strtotime($row->week_beginning_date);
        $row->week_beginning_date = strftime("%Y-%m-%d %H:%M:%S", $row->week_beginning_date);
        $row->module_code_sds = '';

        return $row;
    }

    /**
     * Sync courses with Moodle.
     */
    public function execute() {
        global $CFG, $DB;

        echo "Synching Courses...\n";

        // Create a temp table.
        $this->create_temp_table();

        // Grab data and map times.
        $this->get_all(function($rows) {
            global $DB;

            // Munge the dates.
            $tmp = array();
            foreach ($rows as $row) {
                $row = $this->clean_row($row);
                if ($row) {
                    $tmp[] = $row;
                }
            }

            $DB->insert_records('tmp_connect_courses', $tmp);
        });

        // Load data into the temp table.
        $stats = $this->get_stats();

        // Move data over.
        if ($stats > 50) {
            $this->sync_new_campus();
            $this->sync_deleted_courses();
            $this->sync_updated_courses();
            $this->sync_new_courses();
            $this->map_courses();
        }

        // Drop the temp table.
        $this->destroy_temp_table();
    }
}
