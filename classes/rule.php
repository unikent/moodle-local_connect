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

require_once($CFG->libdir . "/accesslib.php");

/**
 * Connect rule container
 */
class rule extends data
{
    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return "connect_rules";
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array("id", "prefix", "category", "weight");
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
     * Is this role in Moodle?
     * @return boolean
     */
    public function is_in_moodle() {
        return !empty($this->mid);
    }

    /**
     * Map a course to a category
     */
    public static function map($course) {
        global $DB;

        // Accept either an object or a string.
        if (is_object($course)) {
            $course = $course->shortname;
        }

        // Cut up the shortname.
        preg_match("/[a-zA-Z]*/", $course, $matches);
        if (empty($matches)) {
            // Errr.....
            return false;
        }

        $rules = $DB->get_field("connect_rules", "category", array(
            "prefix" => $matches[0]
        ));

        return $rules;
    }
}