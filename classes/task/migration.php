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

namespace local_connect\task;

/**
 * Migrates SDS data from Connect DB to Moodle DB
 */
class migration extends \core\task\scheduled_task
{
    public function get_name() {
        return "SDS Data Import";
    }

    public function execute() {
        global $SDSDB;

        // Setup the SDS database.
        $SDSDB = new \local_connect\util\sdsdb();

        // Run migrations.
        $obj = new \local_connect\SDS\courses();
        $obj->sync();return;
        $obj = new \local_connect\SDS\enrolments();
        $obj->sync();
        $obj = new \local_connect\SDS\groups();
        $obj->sync();
        $obj = new \local_connect\SDS\group_enrolments();
        $obj->sync();
        $obj = new \local_connect\SDS\timetabling();
        $obj->sync();
    }
} 