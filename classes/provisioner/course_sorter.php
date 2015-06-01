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
                $terms[] = base::get_term($course);
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
     * List of MDKs that need to be merged with their parents.
     * array([parent -> array(child, ...)], ...)
     */
    public function get_version_merges() {
        $versionspan = array();

        foreach ($this->_codes as $key => $array) {
            if (count($array) <= 1) {
                continue;
            }

            $versions = array();
            foreach ($array as $mdk) {
                $course = $this->_courses[$mdk];
                $id = "{$course->module_code}_{$course->campusid}_{$course->module_week_beginning}_{$course->module_length}";
                if (!isset($versions[$id])) {
                    $versions[$id] = array();
                }

                $versions[$id][$mdk] = $course->version;
            }

            foreach ($versions as $id => $instanceversions) {
                if (count($instanceversions) <= 1) {
                    continue;
                }

                $parent = null;
                $children = array();

                foreach ($instanceversions as $mdk => $version) {
                    $category = $this->get_category($mdk);
                    if ($category !== 'uncategorised') {
                        if (isset($parent)) {
                            debugging("Multiple parents for {$id}.");
                        }
                        $parent = $this->_courses[$mdk];
                    } else {
                        $children[] = $this->_courses[$mdk];
                    }
                }

                $versionspan[$parent] = $children;
            }
        }

        return $versionspan;
    }

    /**
     * Return the category for a given MDK.
     */
    private function get_category($mdk) {
        $srch = $this->search($mdk);
        if ($srch !== false) {
            list($cat, $key) = $srch;
            return $cat;
        }

        return false;
    }

    /**
     * Find a given MDK.
     */
    private function search($mdk) {
        foreach ($this->_categories as $cat => $array) {
            $key = array_search($mdk, $array);
            if ($key !== false) {
                return array($cat, $key);
            }
        }

        return false;
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
        $srch = $this->search($mdk);
        if ($srch !== false) {
            list($cat, $key) = $srch;
            unset($this->_categories[$cat][$key]);
        }
    }
}