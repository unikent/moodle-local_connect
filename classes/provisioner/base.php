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
        $lists = $sorter->get_lists();

        // Create simple build actions for all courses that are unique.
        foreach ($lists['unique'] as $course) {
            $course = \local_connect\course::from_sql_result($course);
            if ($course->is_unique_shortname($course->shortname, true)) {
                $this->_tree->add_child(new actions\course_create($course));
            } else {
                debugging("{$course->id} was marked as unique.. but wasnt.");
            }
        }

        // Create mostly-simple build actions for all courses that are term-spanned.
        foreach ($lists['term-span'] as $course) {
            $course = \local_connect\course::from_sql_result($course);
            $shortnameext = $this->get_shortnameext($course);
            $course->set_shortname_ext($shortnameext);

            if ($course->is_unique_shortname($course->shortname, true)) {
                $this->_tree->add_child(new actions\course_create($course));
            }
        }
    }

    /**
     * Get the term from dates.
     */
    public static function get_term($course) {
        if ($course->module_length == 12) {
            if ($course->module_week_beginning >= 24) {
                return "SUM";
            }

            if ($course->module_week_beginning >= 12) {
                return "SPR";
            }

            if ($course->module_week_beginning >= 1) {
                return "AUT";
            }
        }

        if ($course->module_length == 24) {
            if ($course->module_week_beginning >= 24) {
                return "SUM/AUT";
            }

            if ($course->module_week_beginning >= 12) {
                return "SPR/SUM";
            }

            if ($course->module_week_beginning >= 1) {
                return "AUT/SPR";
            }
        }

        return "UNK";
    }

    /**
     * Build a shortnameext.
     */
    private function get_shortnameext($course) {
        if (strpos($course->module_code, "WSHOP") === 0) {
            return "(week " . $course->module_week_beginning . ")";
        }

        $term = static::get_term($course);
        if ($term != "UNK") {
            return $term;
        }

        $start = $course->module_week_beginning;
        $end = (int)$start + (int)$course->module_length;
        return "(week {$start}-$end)";
    }

    /**
     * Execute this plan.
     */
    public function execute() {
        $this->_tree->execute();
    }
}
