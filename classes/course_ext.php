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
 * Connect course ext container.
 */
class course_ext extends data
{
    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return 'connect_course_exts';
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array(
            "id", "coursemid", "extension"
        );
    }

    /**
     * A list of immutable fields for this data object.
     */
    protected static function immutable_fields() {
        return array("id");
    }

    /**
     * Set the extension of a course.
     */
    public static function set($mid, $ext) {
        global $DB;

        // Is this just an update?
        $obj = static::get_by('coursemid', $mid);
        if ($obj) {
            $obj->extension = $ext;
            $obj->save();
            return;
        }

        // Nope, insert.
        $DB->insert_record(static::get_table(), array(
            'coursemid' => $mid,
            'extension' => $ext
        ));
    }
}