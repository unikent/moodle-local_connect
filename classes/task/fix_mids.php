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
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect\task;

/**
 * Fixes Connect Moodle IDs
 */
class fix_mids extends \core\task\scheduled_task
{
    public function get_name() {
        return "Fix Moodle IDs";
    }

    public function execute() {
        global $DB;

        // Fix courses.
        $sql = <<<SQL
            SELECT cc.id, cc.mid
            FROM {connect_course} cc
            LEFT OUTER JOIN {course} c ON c.id=cc.mid
            WHERE cc.mid > 0 AND c.id IS NULL
SQL;
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            $record->mid = null;
            $DB->update_record('connect_course', $record);
        }

        $count = count($records);
        echo "Fixed {$count} course MIDs.\n";

        unset($records);

        // Fix users.
        $sql = <<<SQL
            SELECT cu.id, cu.mid
            FROM {connect_user} cu
            LEFT OUTER JOIN {user} u ON u.id=cu.mid
            WHERE cu.mid > 0 AND u.id IS NULL
SQL;
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            $record->mid = null;
            $DB->update_record('connect_user', $record);
        }

        $count = count($records);
        echo "Fixed {$count} user MIDs.";

        unset($records);

        // Fix groups.
        $sql = <<<SQL
            SELECT cg.id, cg.mid
            FROM {connect_group} cg
            LEFT OUTER JOIN {groups} g ON g.id=cg.mid
            WHERE cg.mid > 0 AND g.id IS NULL
SQL;
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            $record->mid = null;
            $DB->update_record('connect_group', $record);
        }

        $count = count($records);
        echo "Fixed {$count} group MIDs.";
    }
} 