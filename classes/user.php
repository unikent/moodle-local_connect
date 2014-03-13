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
 * Connect user container
 */
class user extends data
{
    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return "connect_user";
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array("id", "mid", "ukc", "login", "title", "initials", "family_name");
    }

    /**
     * A list of immutable fields for this data object.
     */
    protected static function immutable_fields() {
        return array("id", "ukc");
    }

    /**
     * A list of key fields for this data object.
     */
    protected static function key_fields() {
        return array("id");
    }

	/**
	 * Is this user in Moodle?
	 * @return boolean [description]
	 */
	public function is_in_moodle() {
		return !empty($this->mid);
	}

	/**
	 * Create this user in Moodle.
	 */
	public function create_in_moodle() {
		global $CFG;

		require_once ($CFG->dirroot . "/user/lib.php");

		if ($this->is_in_moodle()) {
			return $this->mid;
		}

		if (empty($this->login) || empty($this->initials) || empty($this->family_name)) {
			return null;
		}

		$user = new \stdClass();
		$user->username = $this->login;
		$user->firstname = $this->initials;
		$user->lastname = $this->family_name;
		$user->email = $this->login . "@kent.ac.uk";
		$user->auth = "kentsaml";
		$user->password = "not cached";
		$user->confirmed = 1;
		$user->mnethostid = $CFG->mnet_localhost_id;

		$this->mid = user_create_user($user, false);
		$this->save();
	}

	/**
	 * Delete this user from Moodle
	 */
	public function delete() {
		$user = new \stdClass();
		$user->id = $this->mid;
		$user->username = $this->login;
		delete_user($user);

		$this->mid = null;
		$this->save();
	}

	/**
	 * Get a user by Username
	 */
	public static function get_by_username($username) {
		global $DB;

		$user = $DB->get_record("connect_user", array(
			'login' => $username
		));

		$obj = new static();
		$obj->set_class_data($user);

		return $obj;
	}

	/**
	 * Returns a list of all known users in a given role.
	 */
	public static function get_by_role($role) {
		global $DB;

		// Allow a special "staff" case that covers convenors and teachers.
		$selector = '=';
		if ($role === 'staff') {
			$selector = '<>';
			$role = 'student';
		}

		$roleid = $DB->get_field('connect_role', 'id', array(
			'name' => $role
		));

		$sql = "SELECT cu.*
			FROM {connect_user} cu
			INNER JOIN {connect_enrolments} ce ON ce.user=cu.id
			WHERE ce.role $selector :role";
		$data = $DB->get_records_sql($sql, array(
			"role" => $roleid
		));

		$result = array();
		foreach ($data as $obj) {
			if (isset($result[$obj->login]) || empty($obj->login)) {
				continue;
			}

			$user = new static();
            $user->set_class_data($obj);
            $result[$obj->login] = $user;
		}

		return $result;
	}

	/**
	 * Returns a list of all known students.
	 */
	public static function get_students() {
		return static::get_by_role('student');
	}

	/**
	 * Returns a list of all known students.
	 */
	public static function get_staff() {
		return static::get_by_role('staff');
	}
}