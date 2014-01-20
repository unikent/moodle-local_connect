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

namespace local_connect;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared Connect Provider for Moodle - Provides
 * a simple interface to the Shared Connect DB
 */
class sharedbprovider {

    /**
     * Sets up global $SHAREDB moodle_database instance
     *
     * @global stdClass $CFG The global configuration instance.
     * @global stdClass $SHAREDB The global moodle_database instance for Connect.
     * @return void|bool Returns true when finished setting up $SHAREDB. Returns void when $SHAREDB has already been set.
     */
    private static function setup_connect_database() {
        global $CFG, $SHAREDB;

        if (isset($SHAREDB) && get_class($SHAREDB) !== get_class(new static())) {
            return;
        }

        if (!$SHAREDB = \moodle_database::get_driver_instance($CFG->connect->sharedb['driver'], $CFG->connect->sharedb['library'])) {
            throw new \dml_exception('dbdriverproblem', "Unknown driver for connect");
        }

        $SHAREDB->connect(
            $CFG->connect->sharedb['host'],
            $CFG->connect->sharedb['user'],
            $CFG->connect->sharedb['pass'],
            $CFG->connect->sharedb['name'],
            $CFG->connect->sharedb['prefix'],
            $CFG->connect->sharedb['options']
        );

        return true;
    }

    /**
     * Override magic method for call to create the correct global
     * variable (as we obviously want it...)
     */
    public function __call($name, $arguments) {
        global $SHAREDB;

        if (!\local_connect\utils::enable_sharedb()) {
            return false;
        }

        // Ensure we are connected
        self::setup_connect_database();

        // Reflect in this instance, subsequent calls should be routed straight to the DML provider
        $reflectionMethod = new \ReflectionMethod($SHAREDB, $name);
        return $reflectionMethod->invokeArgs($SHAREDB, $arguments);
    }
}