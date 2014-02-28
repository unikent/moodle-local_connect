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
 * Connect data container
 */
abstract class data {
	/** Stores all our data */
	private $_data;

	public function __construct() {
		$this->_data = array();
	}

	/**
	 * A list of valid fields for this data object.
	 */
	protected abstract function valid_fields();

    /**
     * A list of key fields for this data object.
     */
    protected abstract function key_fields();

	/**
	 * A list of immutable fields for this data object.
	 */
	protected function immutable_fields() {
		return array();
	}

	/**
	 * Get all of our data as an object
	 */
	protected final function get_data() {
		return (object)$this->_data;
	}

	/**
	 * Magic method!
	 */
	public function __get($name) {
		if (!in_array($name, $this->valid_fields())) {
			debugging("Invalid field: $name!");
			return null;
		}

		if (isset($this->_data[$name])) {
			return $this->_data[$name];
		}

		return null;
	}

	/**
	 * Magic!
	 */
	public function __set($name, $value) {
		if (!in_array($name, $this->valid_fields())) {
			debugging("Invalid field: $name!");
			return;
		}

		$validation = "validate_" . $name;
		if (method_exists($this, $validation)) {
			if (!$this->$validation($value)) {
				throw new \moodle_exception("Invalid value for field '$name': $value!");
			}
		}

		$this->_data[$name] = $value;
	}

	/**
	 * Is this in Moodle?
	 * 
	 * @return boolean
	 */
	public abstract function is_in_moodle();

	/**
	 * Save to Moodle
	 * 
	 * @return boolean
	 */
	public abstract function create_in_moodle();

	/**
	 * Save to the Connect database
	 * 
	 * @return boolean
	 */
	public function save() {
		// Not implemented.
	}

	/**
	 * Delete from Moodle
	 * 
	 * @return boolean
	 */
	public function delete() {
		// Not implemented.
	}
}