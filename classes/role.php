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
 * Connect role container
 */
class role extends data
{
    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return "connect_role";
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array("id", "name");
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
	 * Returns the moodle ID
	 * @return boolean
	 */
	public function _get_mid() {
		global $DB;
		static $result = array();

		if (!isset($result[$this->id])) {
        	$data = $this->get_data_mapping();
			$result[$this->id] = $DB->get_field('role', 'id', array(
				'shortname' => $data['short']
			), IGNORE_MISSING);
		}

		return $result[$this->id];
	}

	/**
	 * Is this role in Moodle?
	 * @return boolean
	 */
	public function is_in_moodle() {
		return $this->mid !== false;
	}

	/**
	 * Returns our data mappings.
	 * @todo Move this to DB.
	 */
	public function get_data_mapping() {
        static $data_map = array(
            "student" => array(
                "name" => "Student (SDS)",
                "short" => "sds_student",
                "desc" => "Students generally have fewer privileges within a course.",
                "archetype" => "student"
            ),
            "teacher" => array(
                "name" => "Teacher (SDS)",
                "short" => "sds_teacher",
                "desc" => "Teachers can do anything within a course, including changing the activities and grading students.",
                "archetype" => "editingteacher"
            ),
            "convenor" => array(
                "name" => "Convenor (SDS)",
                "short" => "convenor",
                "desc" => "A Convenor has the same permissions as a teacher, but can manually enrol teachers.",
                "archetype" => "editingteacher"
            )
        );

        return isset($data_map[$this->name]) ? $data_map[$this->name] : false;
	}

	/**
	 * Create this role in Moodle.
	 */
	public function create_in_moodle() {
        global $DB;

        $data = $this->get_data_mapping();
        if ($data === false) {
            throw new \moodle_exception("No data mapping for role - {$this->name}!");
        }

        // Create it if it doesnt already exist.
        if (!$DB->record_exists('role', array('shortname' => $data['short']))) {
            call_user_func_array("create_role", array_values($data));
        }
	}

	/**
	 * Delete this role from Moodle
	 */
	public function delete() {
		return delete_role();
	}
}