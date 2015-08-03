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

$services = array(
    'Connect service' => array(
        'functions' => array (
            'local_connect_search_modules',
            'local_connect_get_my_modules',
            'local_connect_push_module',
            'local_connect_link_module',
            'local_connect_unlink_module'
        ),
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled' => 1
    )
);

$functions = array(
    'local_connect_search_modules' => array(
        'classname'   => 'local_connect\external\module',
        'methodname'  => 'search',
        'description' => 'Search modules.',
        'type'        => 'read'
    ),
    'local_connect_get_my_modules' => array(
        'classname'   => 'local_connect\external\module',
        'methodname'  => 'get_my',
        'description' => 'Get a user\'s modules.',
        'type'        => 'read'
    ),
    'local_connect_push_module' => array(
        'classname'   => 'local_connect\external\module',
        'methodname'  => 'push',
        'description' => 'Push a module.',
        'type'        => 'write'
    ),
    'local_connect_link_module' => array(
        'classname'   => 'local_connect\external\module',
        'methodname'  => 'link',
        'description' => 'Link a module.',
        'type'        => 'write'
    ),
    'local_connect_unlink_module' => array(
        'classname'   => 'local_connect\external\module',
        'methodname'  => 'unlink',
        'description' => 'Unlink a module.',
        'type'        => 'write'
    )
);