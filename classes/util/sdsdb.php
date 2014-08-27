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
 * Shared Connect Provider for Moodle - Provides
 * a simple interface to the Shared Connect DB
 */
class SDSDB {
    private static $setup = false;

    /**
     * Sets up global $SDSDB moodle_database instance
     *
     * @global stdClass $CFG The global configuration instance.
     * @global stdClass $SDSDB The global moodle_database instance for Connect.
     * @return void|bool Returns true when finished setting up $SDSDB. Returns void when $SDSDB has already been set.
     */
    private static function setup_database() {
        global $CFG, $SDSDB;

        if (static::$setup) {
            return;
        }

        if (!$SDSDB = \moodle_database::get_driver_instance($CFG->kent->sdsdb['driver'],
                                                              $CFG->kent->sdsdb['library'],
                                                              true)) {
            throw new \dml_exception('dbdriverproblem', "Unknown driver for SDS");
        }

        $SDSDB->connect(
            $CFG->kent->sdsdb['host'],
            $CFG->kent->sdsdb['user'],
            $CFG->kent->sdsdb['pass'],
            $CFG->kent->sdsdb['name'],
            $CFG->kent->sdsdb['prefix'],
            $CFG->kent->sdsdb['options']
        );

        static::$setup = true;

        return true;
    }

    /**
     * Override magic method for call to create the correct global
     * variable (as we obviously want it...)
     */
    public function __call($name, $arguments) {
        global $SDSDB;

        // Ensure we are connected.
        self::setup_database();

        // Reflect in this instance, subsequent calls should be routed straight to the DML provider.
        $method = new \ReflectionMethod($SDSDB, $name);
        return $method->invokeArgs($SDSDB, $arguments);
    }

    /**
     * Is this available?
     */
    public static function available() {
        global $CFG;

        return !empty($CFG->kent->sdsdb['user']);
    }

    /**
     * Dispose SDSDB.
     */
    public static function dispose() {
        global $SDSDB;

        $SDSDB->dispose();
        static::$setup = false;
    }
}