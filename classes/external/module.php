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
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_value;
use external_single_structure;
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
                VALUE_REQUIRED
            )
        ));
    }

    /**
     * Search a list of modules.
     *
     * @param $modulecode
     * @return array [string]
     * @throws \invalid_parameter_exception
     */
    public static function search($modulecode) {
        global $DB;

        $params = self::validate_parameters(self::search_parameters(), array(
            'module_code' => $modulecode
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
     * Search a list of modules.
     *
     * @return array [string]
     * @throws \invalid_parameter_exception
     */
    public static function get_my() {
        $connect = new \local_connect\core();
        return $connect->get_my_courses();
    }

    /**
     * Returns description of get_my() result value.
     *
     * @return external_description
     */
    public static function get_my_returns() {
        return new external_multiple_structure(new external_value(PARAM_RAW, 'DA Page List.')); // TODO?
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function push_parameters() {
        return new external_function_parameters(array(
            'id' => new external_value(
                PARAM_INT,
                'The module to push',
                VALUE_REQUIRED
            )
        ));
    }

    /**
     * Push a module.
     *
     * @param $id
     * @return bool
     * @throws \invalid_parameter_exception
     */
    public static function push($id) {
        if (!\local_connect\util\helpers::can_category_manage()) {
            throw new \moodle_exception('You do not have access to that.');
        }

        $params = self::validate_parameters(self::push_parameters(), array(
            'id' => $id
        ));

        $course = \local_connect\course::get($params['id']);
        if (!$course->create_in_moodle()) {
            throw new \moodle_exception("Could not push course.");
        }

        return $course->mid;
    }

    /**
     * Returns description of push() result value.
     *
     * @return external_description
     */
    public static function push_returns() {
        return new external_single_structure(array(
            new external_value(PARAM_INT, 'Moodle id of the course.')
        ));
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function unlink_parameters() {
        return new external_function_parameters(array(
            'id' => new external_value(
                PARAM_INT,
                'The module to unlink',
                VALUE_REQUIRED
            )
        ));
    }

    /**
     * Unlink a module.
     *
     * @param $id
     * @return bool
     * @throws \invalid_parameter_exception
     */
    public static function unlink($id) {
        if (!\local_connect\util\helpers::can_category_manage()) {
            throw new \moodle_exception('You do not have access to that.');
        }

        $params = self::validate_parameters(self::unlink_parameters(), array(
            'id' => $id
        ));

        $course = \local_connect\course::get($params['id']);
        return $course->unlink();
    }

    /**
     * Returns description of unlink() result value.
     *
     * @return external_description
     */
    public static function unlink_returns() {
        return new external_single_structure(array(
            new external_value(PARAM_BOOL, 'Success or failue (true/false).')
        ));
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function link_parameters() {
        return new external_function_parameters(array(
            'id' => new external_value(
                PARAM_INT,
                'The module to link',
                VALUE_REQUIRED
            ),
            'moodleid' => new external_value(
                PARAM_INT,
                'The Moodle ID to assign this to.',
                VALUE_REQUIRED
            )
        ));
    }

    /**
     * Link a module.
     *
     * @param $id
     * @param $moodleid
     * @return bool
     * @throws \invalid_parameter_exception
     */
    public static function link($id, $moodleid) {
        if (!\local_connect\util\helpers::can_category_manage()) {
            throw new \moodle_exception('You do not have access to that.');
        }

        $params = self::validate_parameters(self::link_parameters(), array(
            'id' => $id,
            'moodleid' => $moodleid
        ));

        $course = \local_connect\course::get($params['id']);
        return $course->link($params['moodleid']);
    }

    /**
     * Returns description of link() result value.
     *
     * @return external_description
     */
    public static function link_returns() {
        return new external_single_structure(array(
            new external_value(PARAM_BOOL, 'Success or failue (true/false).')
        ));
    }
}