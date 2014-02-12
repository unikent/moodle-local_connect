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
	/** Our UKC ID */
	public $uid;

	/** Our Username */
	public $username;

	/** Our firstname (usually an initial) */
	public $firstname;

	/** Our lastname */
	public $lastname;

	/** Our Moodle ID (dont rely on this, use get_moodle_id()) */
	private $moodle_id;

	/**
	 * Create a user object from a username
	 */
	public function __construct($username) {
		global $CONNECTDB;

		$user = $CONNECTDB->get_record('enrollments', array(
			'login' => $username
		), "*", IGNORE_MULTIPLE);

		$this->uid = $user->ukc;
		$this->username = $username;
		$this->firstname = empty($user->initials) ? $username[0] : $user->initials;
		$this->lastname = empty($user->family_name) ? $username[1] : $user->family_name;
	}

	/**
	 * Returns the Moodle user ID (or null)
	 */
	public function get_moodle_id() {
		global $DB;

		if (empty($this->moodle_id)) {
			$user = $DB->get_record('user', array(
				'username' => $this->username
			));

			$this->moodle_id = empty($user) ? null : $user->id;
		}

		return $this->moodle_id;
	}

	/**
	 * Is this user in Moodle?
	 * @return boolean [description]
	 */
	public function is_in_moodle() {
		$userid = $this->get_moodle_id();
		return $userid !== null;
	}

	/**
	 * Create this user in Moodle.
	 */
	public function create_in_moodle() {
		global $CFG;

		require_once ($CFG->dirroot . "/user/lib.php");

		if ($this->is_in_moodle()) {
			return $this->get_moodle_id();
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
}