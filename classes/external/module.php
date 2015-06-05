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
    public static function search_modules_parameters() {
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
    public static function search_modules_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Search a list of modules.
     *
     * @param string $component Limit the search to a component.
     * @param string $search The search string.
     * @return array[string]
     */
    public static function search_modules($modulecode) {
        global $DB;

        $params = self::validate_parameters(self::search_modules_parameters(), array(
            'module_code' => $modulecode,
        ));
        $modulecode = $params['module_code'];

        $like = $DB->sql_like('module_code', ':modulecode');

        return $DB->get_records_select('connect_course', $like, array(
            'modulecode' => "%{$modulecode}%"
        ));
    }

    /**
     * Returns description of search_modules() result value.
     *
     * @return external_description
     */
    public static function search_modules_returns() {
        return new external_multiple_structure(new external_value(PARAM_RAW, 'The module information.'));
    }
}