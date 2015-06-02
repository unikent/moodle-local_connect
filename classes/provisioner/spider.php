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
 * The spider is responsible for interrogating a task tree.
 *
 * @since Moodle 2015
 */
class spider
{
    private $_tree;

    /**
     * Setup.
     */
    public function __construct(actions\base $tree) {
        $this->_tree = $tree;
    }

    /**
     * Search for all types of action.
     */
    private function get_actions($type) {
        foreach ($this->_tree->get_flat_tree() as $child) {
            if ($child->get_task_name() == $type) {
                yield $child;
            }
        }
    }

    /**
     * Returns the create task for a given course, if it exists.
     */
    private function get_create_task($courseid) {
        foreach ($this->get_actions(self::LEAF_COURSE_CREATE) as $action) {
            $course = $action->get_course();
            if ($course->id == $courseid) {
                return $action;
            }
        }
    }

    /**
     * Crawl across the tree and pluck out any tasks we don't want to actually run.
     */
    public function tidy() {
        // Create a list of courses to filter out of the course_create subtree.
        $filterlist = array();

        // Add all children of merge tasks to the list.
        foreach ($this->get_actions('course_merge') as $leaf) {
            foreach ($leaf->get_course_children() as $child) {
                $filterlist[] = $child;
            }
        }

        // Crawl over the create tasks, remove any from the filter list.
        $this->_tree->filter(function($leaf) use($filterlist) {
            if ($leaf->get_task_name() == 'course_create') {
                $compare = $leaf->get_course();
                foreach ($filterlist as $needle) {
                    if ($needle->id == $compare->id) {
                        return false;
                    }
                }
            }

            return true;
        });
    }
}