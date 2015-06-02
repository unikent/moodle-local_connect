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
    private $_parent;
    private $_children;

    /**
     * Constructor.
     */
    public function __construct($parent, $children) {
        parent::__construct();

        $this->_parent = $parent;
        $this->_children = $children;
    }

    /**
     * Return the parent course.
     */
    public function get_parent() {
        return $this->_parent;
    }

    /**
     * Return the children.
     */
    public function get_children() {
        return $this->_children;
    }

    /**
     * Execute this action.
     */
    public function execute() {
        // Is the parent in a thing?
        if (!$this->_parent->is_in_moodle()) {
           $this->_parent->create_in_moodle();
        }

        foreach ($this->_children as $child) {
            // Did we already create this?
            if ($child->is_in_moodle() && $child->mid != $parent->mid) {
                $child->unlink();
                $course = $DB->get_record('course', array(
                    'id' => $child->mid
                ));
                delete_course($course);
            }

            $this->_parent->add_child($child);
        }

        parent::execute();
    }

    /**
     * toString override.
     */
    public function __toString() {
        $mdks = array_map(function($course) {
            return $course->module_delivery_key;
        }, $this->_children);

        $children = implode(', ', $mdks);

        return "merge course {$this->_parent->module_delivery_key}->($children)" . parent::__toString();
    }
}