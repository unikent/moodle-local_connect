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
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Connect utils
 */
class helpers {

    /**
     * Decide what to do with an error.
     */
    public static function error($message) {
        if (get_config("local_connect", "enable_hipchat")) {
            \local_hipchat\Message::send($message, "red", "text");
        }

        debugging($message, DEBUG_DEVELOPER);
    }

    /**
     * Is connect configured properly?
     */
    public static function is_enabled() {
        global $CFG;
        return isset($CFG->local_connect_enable) && $CFG->local_connect_enable;
    }


    /**
     * Returns a JSON list of categories we can manage
     */
    public static function get_connect_course_categories() {
        global $DB, $USER;

        $cachekey = "coursecat{$USER->id}";
        $cache = \cache::make('local_connect', 'ctxperms');

        $catpermissions = $cache->get($cachekey);
        if ($catpermissions) {
            return $catpermissions;
        }

        $catpermissions = array();

        $contextpreload = \context_helper::get_preload_record_columns_sql('x');
        $cats = $DB->get_records_sql("
            SELECT cc.id, cc.name, $contextpreload
            FROM {course_categories} cc
            INNER JOIN {context} x ON (cc.id=x.instanceid AND x.contextlevel=".CONTEXT_COURSECAT.")
        ");

        foreach ($cats as $cat) {
            \context_helper::preload_from_record($cat);
            $context = \context_coursecat::instance($cat->id);

            if (has_capability('moodle/category:manage', $context)) {
                $catpermissions[$cat->id] = $cat->name;
            }
        }

        $cache->set($cachekey, $catpermissions);

        return $catpermissions;
    }

    /**
     * Is this user allowed to manage courses?
     * @return boolean
     */
    public static function can_category_manage() {
        global $DB;

        if (has_capability('moodle/site:config', \context_system::instance())) {
            return true;
        }

        $cats = static::get_connect_course_categories();
        return count($cats) > 0;
    }
}