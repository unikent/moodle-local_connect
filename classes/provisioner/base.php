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
 * @since Moodle 2015
 */
class base
{
	/**
	 * List of actions.
	 * @internal
	 */
	private $_tree;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->_tree = new actions\base();

		$this->build_tree();
	}

	/**
	 * Get a list of actions.
	 */
	public function get_actions() {
		return $this->_tree;
	}

	/**
	 * Build the action tree.
	 * This is the main method.
	 */
	private function build_tree() {
		$sorter = new course_sorter();
	}

	/**
	 * Execute this plan.
	 */
	public function execute() {
		$this->_tree->execute();
	}
}
