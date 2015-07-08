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
 * Base Task
 */
abstract class task_base extends \core\task\scheduled_task
{
    /**
     * Map status to actions.
     * @param $status
     * @param $obj
     */
    public function map_status($status, $obj) {
        switch ($status) {
            case \local_connect\data::STATUS_CREATE:
                echo "  Created: " . $obj->id . "\n";
            break;
            case \local_connect\data::STATUS_MODIFY:
                echo "  Modified: " . $obj->id . "\n";
            break;
            case \local_connect\data::STATUS_DELETE:
                echo "  Deleted: " . $obj->id . "\n";
            break;
            default:
            break;
        }
    }
} 