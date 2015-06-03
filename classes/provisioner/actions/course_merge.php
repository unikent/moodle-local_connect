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
    private $_course;
    private $_child;

    /**
     * Constructor.
     */
    public function __construct($parent, $child) {
        parent::__construct();

        $this->_course = $parent;
        $this->_child = $child;
    }

    /**
     * Get task name.
     */
    public function get_task_name() {
        return 'course_merge';
    }

    /**
     * Return the parent course.
     */
    public function get_parent_course() {
        return $this->_course;
    }

    /**
     * Return the child.
     */
    public function get_child() {
        return $this->_child;
    }

    /**
     * Execute this action.
     */
    public function run() {
        // Is the parent in a thing?
        $this->_course->refresh();
        if (!$this->_course->is_in_moodle()) {
           debugging("Parent course has not been created {$this->_child->id}->{$this->_course->id}.");
           return;
        }

        // Did we already create this?
        $this->_child->refresh();
        if ($this->_child->is_in_moodle()) {
           debugging("Child course has already been created {$this->_child->id}->{$this->_course->id}.");
           return;
        }

        $this->_course->add_child($this->_child);

        parent::run();
    }

    /**
     * toString override.
     */
    public function __toString() {
        return "merge course {$this->_child->id}->{$this->_course->id}" . parent::__toString();
    }
}