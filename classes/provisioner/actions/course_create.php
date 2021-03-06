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
 * Create course action.
 *
 * @since Moodle 2015
 */
class course_create extends base
{
    private $_course;

    /**
     * Constructor.
     * @param $course
     */
    public function __construct($course) {
        parent::__construct();

        $this->_course = $course;
    }

    /**
     * Get task name.
     */
    public function get_task_name() {
        return 'course_create';
    }

    /**
     * Return the course.
     */
    public function get_course() {
        return $this->_course;
    }

    /**
     * Execute this action.
     */
    public function run() {
        if (!$this->_course->create_in_moodle(true)) {
            debugging("Failed to create course {$this->_course->id}");
            return;
        }

        parent::run();
    }

    /**
     * toString override.
     */
    public function __toString() {
        return "create course {$this->_course->id}" . parent::__toString();
    }
}