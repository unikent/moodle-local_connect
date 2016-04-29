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

namespace local_connect\task;

/**
 * Course Sync
 */
class course_sync extends task_base
{
    public function get_name() {
        return "Course Sync";
    }

    public function execute() {
        $enabled = get_config('local_connect', 'enable_course_sync');
        if (!$enabled) {
            return;
        }

        $self = $this;
        \local_connect\course::batch_all(function ($obj) use($self) {
            try {
                $result = $obj->sync();
                $self->map_status($result, $obj);
            } catch (\Excepton $e) {
                $msg = $e->getMessage();
                echo "  Error: {$msg}\n";
            }
        });
    }
}
