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
     * module_delivery_key => course
     */
    private $_courses;

    /**
     * Course list.
     * module_code => (module_delivery_key,..)
     */
    private $_codes;

    /**
     * Categorised list.
     */
    private $_categories = array(
        'unique' => array(),
        'term-span' => array(),
        'campus-span' => array(),
        'full-span' => array(),
        'uncategorised' => array()
    );

    /**
     * Constructor.
     */
    public function __construct() {
        $this->grab_courses();
        $this->sort_lists();
    }

    /**
     * Return the lists.
     */
    public function get_lists() {
        $array = array();
        foreach ($this->_categories as $category => $list) {
            $array[$category] = array();
            foreach ($list as $mdk) {
                $array[$category][] = $this->_courses[$mdk];
            }
        }
        return $array;
    }

    /**
     * Grab courses.
     */
    private function grab_courses() {
        global $DB;

        $this->_courses = array();
        $this->_codes = array();

        $rs = $DB->get_recordset('connect_course', array('mid' => 0, 'deleted' => 0));
        foreach ($rs as $course) {
            if (isset($this->_courses[$course->module_delivery_key])) {
                debugging("Course is not unique: " . $this->_courses[$course->module_delivery_key]);
            }

            $this->_courses[$course->module_delivery_key] = $course;

            // Add to keys list.
            $k = $course->module_code;
            if (!isset($this->_codes[$k])) {
                $this->_codes[$k] = array();
            }
            $this->_codes[$k][] = $course->module_delivery_key;
        }
        $rs->close();
    }

    /**
     * Sort the lists.
     */
    private function sort_lists() {
        foreach ($this->_codes as $key => $array) {
            foreach ($array as $mdk) {
                $this->_categories['uncategorised'][] = $mdk;
            }
        }

        $this->sort_unique();
        $this->sort_spans();
    }

    /**
     * Sort all unique courses.
     */
    private function sort_unique() {
        foreach ($this->_codes as $key => $array) {
            if (count($array) <= 1) {
                $this->move($key, 'unique');
            }
        }
    }

    /**
     * Sort all spanned courses.
     */
    private function sort_spans() {
        foreach ($this->_codes as $key => $array) {
            if (count($array) <= 1) {
                continue;
            }

            $campuses = $terms = array();
            foreach ($array as $mdk) {
                $course = $this->_courses[$mdk];
                $campuses[] = $course->campusid;
                $terms[] = $course->module_week_beginning;
            }

            $campuses = count(array_unique($campuses));
            $terms = count(array_unique($terms));

            if ($campuses > 1 && $terms > 1) {
                $this->move($key, 'full-span');
                continue;
            }

            if ($campuses > 1) {
                $this->move($key, 'campus-span');
                continue;
            }

            if ($terms > 1) {
                $this->move($key, 'term-span');
                continue;
            }
        }
    }

    /**
     * Move all courses matching a shortcode to a list.
     */
    private function move($code, $list) {
        foreach ($this->_codes[$code] as $mdk) {
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