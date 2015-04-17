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

namespace local_connect\provisioner;

defined('MOODLE_INTERNAL') || die();

/**
 * Moodle provisioning toolkit.
 * 
 * This sorts out the courses into the following categories
 * so the provisioner can work out what to do with them:
 *  - Unique (Ain't nothing like me)
 *  - Term-Span (Same module code spans multiple terms)
 *  - Campus-Span (Same module code spans multiple campuses)
 *  - Full-Span (Same module code spans multiple terms and campuses)
 *
 * @since Moodle 2015
 */
class course_sorter
{
	/**
	 * Course list.
	 * module_code => (module_delivery_key,..)
	 */
	private $_courses;

	/**
	 * Categorised list.
	 */
	private $_categories = array(
		'unique' => array(),
		'term-span' => array(),
		'campus-span' => array(),
		'full-span' => array()
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $DB;

		$this->_courses = array();

		$rs = $DB->get_recordset('connect_course');
		$rs->close();
	}

	/**
	 * Move all courses matching a shortcode to a list.
	 */
	private function move($code, $list) {
		foreach ($this->_courses[$code] as $mdk) {
			$this->unlist($mdk);
			$this->_categories[$list][] = $mdk;
		}
	}

	/**
	 * Unlist a given MDK.
	 */
	private function unlist($mdk) {
		foreach ($this->_categories as $k => $array) {
			$key = array_search($mdk, $array);
			if ($key !== false) {
			    unset($this->_categories[$k][$key]);
			}
		}
	}
}