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

global $CFG;
require_once($CFG->libdir . '/stomp/Stomp.php');

/**
 * Connect Provider for Moodle - Provides
 * a simple interface to Connect STOMP
 */
class stompprovider {

    /**
     * Sets up global $STOMP instance
     *
     * @global stdClass $CFG The global configuration instance.
     * @global stdClass $STOMP The global STOMP instance for Connect.
     * @return void|bool Returns true when finished setting up $STOMP. Returns void when $STOMP has already been set.
     */
    private static function setup() {
        global $CFG, $STOMP;

        if (isset($STOMP) && get_class($STOMP) !== get_class(new static())) {
            return;
        }

        $STOMP = new \FuseSource\Stomp\Stomp($CFG->connect->stomp);
        $STOMP->connect();

        return true;
    }

    /**
     * Override magic method for call to create the correct global
     * variable (as we obviously want it...)
     */
    public function __call($name, $arguments) {
        global $STOMP;

        // Ensure we are connected
        self::setup();

        // Reflect in this instance, subsequent calls should be routed straight to the DML provider
        $reflectionMethod = new \ReflectionMethod($STOMP, $name);
        return $reflectionMethod->invokeArgs($STOMP, $arguments);
    }
}