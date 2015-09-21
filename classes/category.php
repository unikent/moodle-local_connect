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
     * @param $id
     * @return static
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
            array(
                'department' => '75',
                'idnumber' => '10'
            ),
            array(
                'department' => '6',
                'idnumber' => '49'
            ),
            array(
                'department' => '11',
                'idnumber' => '33'
            ),
            array(
                'department' => '48',
                'idnumber' => '22'
            ),
            array(
                'department' => '8',
                'idnumber' => '32'
            ),
            array(
                'department' => '47',
                'idnumber' => '30'
            ),
            array(
                'department' => '61',
                'idnumber' => '26'
            ),
            array(
                'department' => '10',
                'idnumber' => '23'
            ),
            array(
                'department' => '35',
                'idnumber' => '27'
            ),
            array(
                'department' => '78',
                'idnumber' => '27'
            ),
            array(
                'department' => '28',
                'idnumber' => '9'
            ),
            array(
                'department' => '37',
                'idnumber' => '16',
                'rule' => 'LW8'
            ),
            array(
                'department' => '37',
                'idnumber' => '16',
                'rule' => 'LW9'
            ),
            array(
                'department' => '37',
                'idnumber' => '18'
            ),
            array(
                'department' => '37',
                'idnumber' => '19',
                'rule' => 'WSHOP'
            ),
            array(
                'department' => '45',
                'idnumber' => '29'
            ),
            array(
                'department' => '9',
                'idnumber' => '34'
            ),
            array(
                'department' => '1',
                'idnumber' => '50'
            ),
            array(
                'department' => '1',
                'idnumber' => '51',
                'rule' => 'DR'
            ),
            array(
                'department' => '1',
                'idnumber' => '53',
                'rule' => 'CR'
            ),
            array(
                'department' => '1',
                'idnumber' => '54',
                'rule' => 'FI'
            ),
            array(
                'department' => '1',
                'idnumber' => '56',
                'rule' => 'FA'
            ),
            array(
                'department' => '1',
                'idnumber' => '55',
                'rule' => 'HA'
            ),
            array(
                'department' => '1',
                'idnumber' => '52',
                'rule' => 'MU'
            ),
            array(
                'department' => '25',
                'idnumber' => '7'
            ),
            array(
                'department' => '15',
                'idnumber' => '3'
            ),
            array(
                'department' => '36',
                'idnumber' => '67'
            ),
            array(
                'department' => '36',
                'idnumber' => '68',
                'rule' => 'EC8'
            ),
            array(
                'department' => '36',
                'idnumber' => '68',
                'rule' => 'EC9'
            ),
            array(
                'department' => '16',
                'idnumber' => '8'
            ),
            array(
                'department' => '3',
                'idnumber' => '57'
            ),
            array(
                'department' => '4',
                'idnumber' => '37'
            ),
            array(
                'department' => '4',
                'idnumber' => '41',
                'rule' => 'CL'
            ),
            array(
                'department' => '4',
                'idnumber' => '42',
                'rule' => 'CP'
            ),
            array(
                'department' => '4',
                'idnumber' => '38',
                'rule' => 'LZ'
            ),
            array(
                'department' => '4',
                'idnumber' => '38',
                'rule' => 'LL'
            ),
            array(
                'department' => '4',
                'idnumber' => '48',
                'rule' => 'FR'
            ),
            array(
                'department' => '4',
                'idnumber' => '45',
                'rule' => 'GE'
            ),
            array(
                'department' => '4',
                'idnumber' => '47',
                'rule' => 'LS'
            ),
            array(
                'department' => '4',
                'idnumber' => '46',
                'rule' => 'IT'
            ),
            array(
                'department' => '4',
                'idnumber' => '44',
                'rule' => 'LA'
            ),
            array(
                'department' => '4',
                'idnumber' => '39',
                'rule' => 'PL'
            ),
            array(
                'department' => '4',
                'idnumber' => '40',
                'rule' => 'TH'
            ),
            array(
                'department' => '4',
                'idnumber' => '62',
                'rule' => 'HM'
            ),
            array(
                'department' => '5',
                'idnumber' => '35'
            ),
            array(
                'department' => '5',
                'idnumber' => '36',
                'rule' => 'HI8'
            ),
            array(
                'department' => '5',
                'idnumber' => '36',
                'rule' => 'HI9'
            ),
            array(
                'department' => '17',
                'idnumber' => '6'
            ),
            array(
                'department' => '24',
                'idnumber' => '4'
            ),
            array(
                'department' => '26',
                'idnumber' => '5'
            ),
            array(
                'department' => '38',
                'idnumber' => '21'
            ),
            array(
                'department' => '39',
                'idnumber' => '28'
            ),
            array(
                'department' => '40',
                'idnumber' => '13'
            ),
            array(
                'department' => '40',
                'idnumber' => '14',
                'rule' => 'TZ'
            ),
            array(
                'department' => '60',
                'idnumber' => '23'
            ),
            array(
                'department' => '60',
                'idnumber' => '25',
                'rule' => 'UN8'
            ),
            array(
                'department' => '60',
                'idnumber' => '24',
                'rule' => 'WSHOP'
            ),
            array(
                'department' => '12',
                'idnumber' => '50'
            ),
            array(
                'department' => '12',
                'idnumber' => '53',
                'rule' => 'CR'
            ),
            array(
                'department' => '12',
                'idnumber' => '56',
                'rule' => 'FA'
            ),
            array(
                'department' => '12',
                'idnumber' => '52',
                'rule' => 'MU'
            ),
            array(
                'department' => '12',
                'idnumber' => '64',
                'rule' => 'MFA'
            ),
            array(
                'department' => '85',
                'idnumber' => '50'
            ),
            array(
                'department' => '85',
                'idnumber' => '55',
                'rule' => 'HA'
            ),
            array(
                'department' => '69',
                'idnumber' => '13'
            ),
            array(
                'department' => '72',
                'idnumber' => '10'
            )
        );
    }
}