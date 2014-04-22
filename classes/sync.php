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

namespace local_connect;

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
     * Grab a list of enrolments due to be deleted (that have not yet been deleted)
     */
    public static function get_deleted_enrolments() {
        global $DB;

        $data = $DB->get_records_sql("SELECT ce.id
                                        FROM {connect_enrolments} ce
                                            INNER JOIN {connect_user} cu ON ce.userid=cu.id
                                            INNER JOIN {connect_course} cc ON ce.courseid=cc.id
											INNER JOIN {user_enrolments} ue ON ue.userid=cu.mid
											INNER JOIN {enrol} e ON e.id=ue.enrolid AND e.courseid=cc.mid
                                        WHERE ce.deleted=1");

        return self::map_set($data);
    }
}