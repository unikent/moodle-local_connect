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

namespace local_connect;

defined('MOODLE_INTERNAL') || die();

/**
 * Connect timetabling container
 */
class timetabling extends data
{
    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return "connect_timetabling";
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array("id", "typeid", "userid", "courseid", "roomid", "starts", "ends", "day", "weeks");
    }

    /**
     * Returns an array of fields that link to other databasepods.
     * fieldname -> classname
     */
    protected static function linked_fields() {
        return array(
            'typeid' => '\\local_connect\\type',
            'userid' => '\\local_connect\\user',
            'courseid' => '\\local_connect\\course',
            'roomid' => '\\local_connect\\room'
        );
    }

    /**
     * A list of immutable fields for this data object.
     */
    protected static function immutable_fields() {
        return array("id");
    }

    /**
     * A list of key fields for this data object.
     */
    protected static function key_fields() {
        return array("id");
    }

    /**
     * Returns the date of a given occurrence.
     * @param $occurrence
     * @return int
     */
    public function occurrence_date($occurrence) {
        $week = week::get_by('week_beginning', $occurrence);
        $date = (object)date_parse($week->week_beginning_date);

        // Apparently strtotime magically works out what we want
        // and translates the "I want {Thursday} of this week.".
        return strtotime("{$this->day} {$date->day}-{$date->month}-{$date->year} GMT");
    }

    /**
     * Get each occurrence of this event.
     * Returns each week this event falls on.
     */
    public function get_occurrences() {
        $weeks = explode('-', $this->weeks);

        if (count($weeks) > 1) {
            $s = (int)$weeks[0];
            $e = (int)$weeks[1];

            $weeks = array();
            for ($i = $s; $i <= $e; $i++) {
                $weeks[] = $i;
            }
        }

        return $weeks;
    }

    /**
     * Return start time for a given occurrence.
     * @param $occurrence
     * @return int
     */
    public function get_start_time($occurrence) {
        $date = $this->occurrence_date($occurrence);
        return strtotime("{$this->starts} GMT", $date);
    }

    /**
     * Return start time for a given occurrence.
     * @param $occurrence
     * @return int
     */
    public function get_end_time($occurrence) {
        $date = $this->occurrence_date($occurrence);
        return strtotime("{$this->ends} GMT", $date);
    }
}