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
    private $_course_children;

    /**
     * Constructor.
     */
    public function __construct($parent, $children) {
        parent::__construct();

        $this->_course = $parent;
        $this->_course_children = $children;
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
    public function get_course() {
        return $this->_course;
    }

    /**
     * Return the children.
     */
    public function get_course_children() {
        return $this->_course_children;
    }

    /**
     * Execute this action.
     */
    public function execute() {
        // Is the parent in a thing?
        if (!$this->_course->is_in_moodle()) {
           $this->_course->create_in_moodle();
        }

        foreach ($this->_course_children as $child) {
            // Did we already create this?
            if ($child->is_in_moodle() && $child->mid != $parent->mid) {
                $child->unlink();
                $course = $DB->get_record('course', array(
                    'id' => $child->mid
                ));
                delete_course($course);
            }

            $this->_course->add_child($child);
        }

        parent::execute();
    }

    /**
     * toString override.
     */
    public function __toString() {
        $mdks = array_map(function($course) {
            return $course->module_delivery_key;
        }, $this->_course_children);

        $children = implode(', ', $mdks);

        return "merge course {$this->_course->module_delivery_key}->($children)" . parent::__toString();
    }
}