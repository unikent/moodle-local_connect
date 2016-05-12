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
     * Internal tree spider.
     * @internal
     */
    private $_spider;

    /**
     * Are we prepared?
     * @internal
     */
    private $_prepared;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->_prepared = false;
    }

    /**
     * Prepare the provisioner.
     */
    public function prepare() {
        if ($this->_prepared) {
            debugging("Already prepared provisioner.. why do it twice?");
            return false;
        }

        $this->_tree = new actions\base();
        $this->_spider = new spider($this->_tree);

        $this->build_tree();
        $this->_spider->tidy();

        $this->_prepared = true;

        return $this->_prepared;
    }

    /**
     * Get a list of actions.
     */
    public function get_tree() {
        return $this->_tree;
    }

    /**
     * Build the action tree.
     * This is the main method.
     */
    private function build_tree() {
        $sorter = new course_sorter();
        $lists = $sorter->get_lists();

        // Create simple build actions for all courses that are unique.
        foreach ($lists['unique'] as $course) {
            if ($course->is_unique_shortname($course->shortname, true)) {
                $this->_tree->add_child(new actions\course_create($course));
            } else {
                debugging("{$course->id} was marked as unique.. but wasnt.");
            }
        }

        // Create mostly-simple build actions for all courses that are term-spanned.
        foreach ($lists['term-span'] as $course) {
            $shortnameext = $course->generate_shortname_ext();
            $course->set_shortname_ext($shortnameext);

            if ($course->is_unique_shortname($course->shortname, true)) {
                $this->_tree->add_child(new actions\course_create($course));
            }
        }

        // Create mostly-simple build actions for all courses that are campus-spanned.
        foreach ($lists['campus-span'] as $course) {
            $shortnameext = $course->generate_shortname_ext();
            $course->set_shortname_ext($shortnameext);

            if ($course->is_unique_shortname($course->shortname, true)) {
                $this->_tree->add_child(new actions\course_create($course));
            }
        }

        // Merge version-spanned.
        $merges = $sorter->get_version_merges();
        foreach ($merges as $merge) {
            $parent = $merge['parent'];
            $children = $merge['children'];

            // Find the parent create task.
            $leaf = $this->_spider->get_create_task($parent->id);
            if ($leaf) {
                foreach ($children as $child) {
                    $leaf->add_child(new actions\course_merge($parent, $child));
                }
            } else {
                debugging("Couldn't find parent for merge task: {$parent->module_code}");
            }
        }
    }

    /**
     * Get the term from dates.
     *
     * @deprecated Use the method in the course itself.
     * @param $course
     * @return string
     */
    public static function get_term($course) {
        debugging("Deprecated method used: base::get_term");
        if (!is_object($course)) {
            $course = \local_connect\course::from_sql_result($course);
        }

        return $course->get_term();
    }

    /**
     * Execute this plan.
     */
    public function execute() {
        if (!$this->_prepared) {
            throw new \moodle_exception("Did not prepare provisioner.");
        }

        $this->_tree->execute();
    }
}
