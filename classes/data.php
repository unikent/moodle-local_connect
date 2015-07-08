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
 * Connect data container
 */
abstract class data {
    use \local_kent\traits\databasepod;

    const STATUS_NONE = 0;
    const STATUS_CREATE = 1;
    const STATUS_MODIFY = 2;
    const STATUS_DELETE = 3;
    const STATUS_ERROR = 4;

    /**
     * Is this in Moodle?
     *
     * @return boolean
     */
    public function is_in_moodle() {
        debugging("is_in_moodle() has not been implemented for this!", DEBUG_DEVELOPER);
    }

    /**
     * Save to Moodle
     *
     * @return boolean
     */
    public function create_in_moodle() {
        debugging("create_in_moodle() has not been implemented for this!", DEBUG_DEVELOPER);
    }

    /**
     * Delete from Moodle
     *
     * @return boolean
     */
    public function delete() {
        debugging("delete() has not been implemented for this!", DEBUG_DEVELOPER);
    }

    /**
     * Sync with Moodle
     *
     * @param bool $dry
     * @return bool
     */
    public function sync($dry = false) {
        debugging("sync() has not been implemented for this!", DEBUG_DEVELOPER);
    }
}