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

namespace local_connect\provisioner\actions;

defined('MOODLE_INTERNAL') || die();

/**
 * Moodle provisioning toolkit.
 * Merge course action.
 *
 * @since Moodle 2015
 */
class course_merge extends base
{
    private $_data;

    /**
     * Constructor.
     */
    public function __construct($data) {
        parent::__construct();

        $this->_data = $data;
    }

    /**
     * Execute this action.
     */
    public function execute() {
        // TODO.
        parent::execute();
    }

    /**
     * toString override.
     */
    public function __toString() {
        return "Merge course: " . $this->_data['shortname'] . ".\n" . parent::__toString();
    }
}