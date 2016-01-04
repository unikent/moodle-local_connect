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
 * Sync a specified courses enrolments and groups.
 */
class course_fast_sync extends \core\task\adhoc_task
{
    public function get_component() {
        return 'local_connect';
    }

    public function execute() {
        $data = (array)$this->get_custom_data();

        // Grab the course.
        $course = \local_connect\course::get($data['courseid']);
        if ($course) {
            $course->sync_enrolments();
            $course->sync_groups();
        }
    }

    /**
     * Setter for $customdata.
     * @param mixed $customdata (anything that can be handled by json_encode)
     * @throws \moodle_exception
     */
    public function set_custom_data($customdata) {
        if (empty($customdata['courseid'])) {
            throw new \moodle_exception("Course ID cannot be empty!");
        }

        return parent::set_custom_data($customdata);
    }
}
