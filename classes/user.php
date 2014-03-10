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
	/** Our Moodle ID (dont rely on this, use moodle_id) */
	private $_moodle_id;

    /**
     * The name of our connect table.
     */
    protected function get_table() {
        return null;
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final function valid_fields() {
        return array("uid", "username", "firstname", "lastname");
    }

    /**
     * A list of immutable fields for this data object.
     */
    protected function immutable_fields() {
        return array("uid");
    }

    /**
     * A list of key fields for this data object.
     */
    protected function key_fields() {
        return array("username");
    }

	/**
	 * Returns the Moodle user ID (or null)
	 */
	public function _get_moodle_id() {
		global $DB;

		if (empty($this->_moodle_id)) {
			$user = $DB->get_record('user', array(
				'username' => $this->username
			));

			$this->_moodle_id = empty($user) ? null : $user->id;
		}

		return $this->_moodle_id;
	}

	/**
	 * Is this user in Moodle?
	 * @return boolean [description]
	 */
	public function is_in_moodle() {
		$userid = $this->moodle_id;
		return $userid !== null;
	}

	/**
	 * Create this user in Moodle.
	 */
	public function create_in_moodle() {
		global $CFG;

		require_once ($CFG->dirroot . "/user/lib.php");

		if ($this->is_in_moodle()) {
			return $this->moodle_id;
		}

		if (empty($this->username) || empty($this->firstname) || empty($this->lastname)) {
			return null;
		}

		$user = new \stdClass();
		$user->username = $this->username;
		$user->firstname = $this->firstname;
		$user->lastname = $this->lastname;
		$user->email = $this->username . "@kent.ac.uk";
		$user->auth = "kentsaml";
		$user->password = "not cached";
		$user->confirmed = 1;
		$user->mnethostid = $CFG->mnet_localhost_id;

		$this->moodle_id = user_create_user($user, false);
	}

	/**
	 * Delete this user from Moodle
	 */
	public function delete() {
		$user = new \stdClass();
		$user->id = $this->moodle_id;
		$user->username = $this->username;
		delete_user($user);

		$this->moodle_id = null;
	}

	/**
	 * Get a user by Username
	 */
	public static function get($username) {
		global $CONNECTDB;

		$user = $CONNECTDB->get_record('enrollments', array(
			'login' => $username
		), "ukc, initials as firstname, family_name as lastname, login as username", IGNORE_MULTIPLE);

		$obj = new static();
		$obj->set_data($user);

		return $obj;
	}

	/**
	 * Returns a list of all known students.
	 */
	public static function get_by_role($role) {
		global $CONNECTDB;

		// Allow a special "staff" case that covers convenors and teachers.
		$selector = '=';
		if ($role === 'staff') {
			$selector = '<>';
			$role = 'student';
		}

		$sql = "SELECT e.login as username, e.ukc as uid, e.initials as firstname, e.family_name as lastname
			FROM {enrollments} e
				WHERE e.role $selector :role
			GROUP BY e.login";
		$data = $CONNECTDB->get_records_sql($sql, array(
			"role" => $role
		));

		$result = array();
		foreach ($data as $obj) {
			if (isset($result[$obj->username]) || empty($obj->username)) {
				continue;
			}

			$user = new static();
            $user->set_data($obj);
            $result[$obj->username] = $user;
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