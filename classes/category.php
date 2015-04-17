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
 * Connect category container.
 * Unlike most connect classes, this is not a data type.
 */
class category
{
    private $id;

    /**
     * Returns an object of type category in the same
     * manner the rest of connect creates objects.
     */
    public static function get($id) {
        $obj = new static();
        $obj->id = $id;
        return $obj;
    }

    /**
     * Get enrollments for this Category
     */
    public function _get_enrolments() {
        return enrolment::get_for_category($this);
    }

    /**
     * Get the SDS -> Moodle IDNumber map table.
     * SDS Department, Moodle IDNumber, Rule
     */
    public static function get_map_table() {
        return array(
            array('75', '10'),
            array('6', '49'),
            array('11', '33'),
            array('48', '22'),
            array('8', '32'),
            array('47', '30'),
            array('61', '26'),
            array('10', '23'),
            array('35', '27'),
            array('28', '9'),
            array('37', '16', 'LW8'),
            array('37', '16', 'LW9'),
            array('37', '18'),
            array('37', '19', 'WSHOP'),
            array('45', '29'),
            array('9', '34'),
            array('1', '50'),
            array('1', '51', 'DR'),
            array('1', '53', 'CR'),
            array('1', '54', 'FI'),
            array('1', '56', 'FA'),
            array('1', '55', 'HA'),
            array('1', '52', 'MU'),
            array('25', '7'),
            array('15', '3'),
            array('36', '12'),
            array('16', '8'),
            array('3', '57'),
            array('4', '37'),
            array('4', '41', 'CL'),
            array('4', '42', 'CP'),
            array('4', '38', 'LZ'),
            array('4', '38', 'LL'),
            array('4', '48', 'FR'),
            array('4', '45', 'GE'),
            array('4', '47', 'LS'),
            array('4', '46', 'IT'),
            array('4', '44', 'LA'),
            array('4', '39', 'PL'),
            array('4', '40', 'TH'),
            array('5', '35'),
            array('5', '36', 'HI8'),
            array('5', '36', 'HI9'),
            array('17', '6'),
            array('24', '4'),
            array('26', '5'),
            array('38', '21'),
            array('39', '28'),
            array('40', '13'),
            array('40', '14', 'TZ'),
            array('60', '23'),
            array('60', '25', 'UN8'),
            array('60', '24', 'WSHOP'),
            array('12', '53', 'CR'),
            array('12', '56', 'FA'),
            array('12', '52', 'MU')
        );
    }
}