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

/**
 * Local stuff for Moodle Connect
 *
 * @package    local_connect
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_value;
use external_multiple_structure;
use external_function_parameters;

/**
 * Connect's module external services.
 */
class module extends external_api
{
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function search_parameters() {
        return new external_function_parameters(array(
            'module_code' => new external_value(
                PARAM_RAW,
                'The search string',
                VALUE_DEFAULT,
                ''
            )
        ));
    }

    /**
     * Expose to AJAX
     * @return boolean
     */
    public static function search_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Search a list of modules.
     *
     * @param $modulecode
     * @return array [string]
     * @throws \invalid_parameter_exception
     * @internal param string $component Limit the search to a component.
     * @internal param string $search The search string.
     */
    public static function search($modulecode) {
        global $DB;

        $params = self::validate_parameters(self::search_parameters(), array(
            'module_code' => $modulecode,
        ));
        $modulecode = $params['module_code'];

        $like = $DB->sql_like('module_code', ':modulecode');

        return $DB->get_records_select('connect_course', $like, array(
            'modulecode' => "%{$modulecode}%"
        ));
    }

    /**
     * Returns description of search() result value.
     *
     * @return external_description
     */
    public static function search_returns() {
        return new external_multiple_structure(new external_value(PARAM_RAW, 'The module information.'));
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_my_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * Expose to AJAX
     * @return boolean
     */
    public static function get_my_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Search a list of modules.
     *
     * @return array [string]
     * @throws \invalid_parameter_exception
     * @internal param string $component Limit the search to a component.
     * @internal param string $search The search string.
     */
    public static function get_my() {
        global $DB;

        $courses = array();
        if (!has_capability("local/connect:helpdesk", \context_system::instance())) {
            $cats = \local_connect\util\helpers::get_connect_course_categories();
            $courses = \local_connect\course::get_by_category($cats);
        } else {
            $courses = \local_connect\course::get_all();
        }

        // Grab a list of campus IDs.
        $campusids = array();
        $campuses = $DB->get_recordset('connect_campus');
        foreach ($campuses as $campus) {
            $campusids[$campus->id] = $campus->name;
        }
        $campuses->close();

        // Find all merged modules.
        $merged = array();
        foreach ($courses as $course) {
            if (empty($course->mid)) {
                continue;
            }

            if (!isset($merged[$course->mid])) {
                $merged[$course->mid] = array();
            }

            $merged[$course->mid][] = $course;
        }

        $merged = array_filter($merged, function($a) {
            return count($a) > 1;
        });

        // Process everything.
        $mergerefs = array();
        $out = array();
        foreach ($courses as $course) {
            $coursedata = $course->get_data();

            if (isset($campusids[$coursedata->campusid])) {
                $coursedata->campus = $campusids[$coursedata->campusid];
            }

            if (!isset($merged[$course->mid])) {
                $out[] = $coursedata;
                continue;
            }

            if (isset($mergerefs[$course->mid])) {
                $obj = $mergerefs[$course->mid];
                $obj->children[] = $coursedata;
                continue;
            }

            // This is a merged module, create a skeleton.
            $merge = clone($coursedata);
            $merge->module_title = $course->shortname;
            $merge->campus_desc = $course->campus_name;
            $merge->children = array($coursedata);

            $mergerefs[$course->mid] = $merge;

            $out[] = $merge;
        }

        return $out;
    }

    /**
     * Returns description of get_my() result value.
     *
     * @return external_description
     */
    public static function get_my_returns() {
        return new external_multiple_structure(new external_value(PARAM_RAW, 'DA Page List.'));
    }
}