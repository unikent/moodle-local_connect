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

namespace local_connect\experimental;

defined('MOODLE_INTERNAL') || die();

/**
 * Connect Sync Scripts
 */
class sync
{
    /**
     * Map a dataset to an id array
     */
    private static function map_set($data) {
        $ids = array();

        foreach ($data as $datum) {
            $ids[] = $datum->id;
        }

        return $ids;
    }

    /**
     * Map a list of userid => courseid => roleid
     */
    private static function map_structure($data) {
        $ret = array();

        foreach ($data as $row) {
            if (!isset($ret[$row->userid])) {
                $ret[$row->userid] = array();
            }

            $ret[$row->userid][$row->courseid] = $row->roleid;
        }

        return $ret;
    }

    /**
     * Grab a list of groups that need to be created.
     */
    public static function get_new_groups() {
        global $DB;

        $data = $DB->get_records_sql("SELECT cg.id
                                        FROM {connect_group} cg
                                        INNER JOIN {connect_course} cc ON cc.id=cg.courseid
                                        LEFT OUTER JOIN {groups} g ON g.courseid=cc.mid AND g.name=cg.name
                                        WHERE g.id IS NULL AND cc.mid != 0");

        return self::map_set($data);
    }

    /**
     * Returns a list of group enrolments to be created.
     */
    public static function get_new_group_enrolments() {
        global $DB;

        $data = $DB->get_records_sql("SELECT cge.id
                                        FROM {connect_group_enrolments} cge
                                        INNER JOIN {connect_group} cg ON cg.id = cge.groupid
                                        INNER JOIN {connect_user} cu ON cu.id = cge.userid
                                        LEFT OUTER JOIN {groups_members} gm ON gm.groupid = cg.mid AND gm.userid = cu.mid
                                        WHERE gm.id IS NULL AND cg.mid != 0 AND cge.deleted = 0");

        return self::map_set($data);
    }

    /**
     * Returns a list of group enrolments to be deleted.
     */
    public static function get_deleted_group_enrolments() {
        global $DB;

        $data = $DB->get_records_sql("SELECT cge.id
                                        FROM {connect_group_enrolments} cge
                                        INNER JOIN {connect_group} cg ON cg.id = cge.groupid
                                        INNER JOIN {connect_user} cu ON cu.id = cge.userid
                                        LEFT OUTER JOIN {groups_members} gm ON gm.groupid = cg.mid AND gm.userid = cu.mid
                                        WHERE gm.id IS NOT NULL AND cg.mid != 0 AND cge.deleted = 1");

        return self::map_set($data);
    }
}