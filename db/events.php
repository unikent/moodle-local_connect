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

$observers = array (

    array (
        'eventname' => '\core\event\course_created',
        'callback' => '\local_connect\observers::course_created',
    ),

    array (
        'eventname' => '\core\event\course_updated',
        'callback' => '\local_connect\observers::course_updated',
    ),

    array (
        'eventname' => '\core\event\course_deleted',
        'callback' => '\local_connect\observers::course_deleted',
    ),

    array (
        'eventname' => '\core\event\user_created',
        'callback' => '\local_connect\observers::user_created',
    ),

    array (
        'eventname' => '\core\event\user_deleted',
        'callback' => '\local_connect\observers::user_deleted',
    ),

    array (
        'eventname' => '\core\event\group_created',
        'callback' => '\local_connect\observers::group_created',
    ),

    array (
        'eventname' => '\core\event\group_deleted',
        'callback' => '\local_connect\observers::group_deleted',
    ),

    array (
        'eventname' => '\core\event\group_member_added',
        'callback' => '\local_connect\observers::group_member_added',
    ),

    array (
        'eventname' => '\core\event\group_member_removed',
        'callback' => '\local_connect\observers::group_member_removed',
    )

);