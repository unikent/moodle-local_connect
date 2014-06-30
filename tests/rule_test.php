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

defined('MOODLE_INTERNAL') || die();

/**
 * Tests new Kent rule code
 */
class kent_rule_tests extends \local_connect\tests\connect_testcase
{
    /**
     * Create some test rules.
     */
    private function create_rules() {
        global $DB;

        $DB->insert_record('connect_rules', array(
            'prefix' => 'TST',
            'category' => 1,
            'weight' => 50
        ));

        $DB->insert_record('connect_rules', array(
            'prefix' => 'TST1',
            'category' => 3,
            'weight' => 40
        ));

        $DB->insert_record('connect_rules', array(
            'prefix' => 'TST2',
            'category' => 2,
            'weight' => 60
        ));

        $DB->insert_record('connect_rules', array(
            'prefix' => 'TST3',
            'category' => 4,
            'weight' => 50
        ));
    }

    /**
     * Test simple rule mapping.
     */
    public function test_basic_mapping() {
        $this->resetAfterTest();

        $this->create_rules();

        $this->assertEquals(1, \local_connect\rule::map("TST814"));
        $this->assertEquals(1, \local_connect\rule::map("TST816B"));
        $this->assertEquals(1, \local_connect\rule::map("TST124"));
        $this->assertEquals(1, \local_connect\rule::map("TST1"));
        $this->assertEquals(1, \local_connect\rule::map("TST992"));
        $this->assertEquals(1, \local_connect\rule::map("TSTcourse2A"));
    }

    /**
     * Test extended rule mapping.
     */
    public function test_extended_mapping() {
        $this->resetAfterTest();

        $this->create_rules();

        $this->assertEquals(4, \local_connect\rule::map("TST3823"));
    }

    /**
     * Test weighted rule mapping.
     */
    public function test_weighted_mapping() {
        $this->resetAfterTest();

        $this->create_rules();

        $this->assertEquals(1, \local_connect\rule::map("TST1823"));
        $this->assertEquals(2, \local_connect\rule::map("TST2823"));
    }
}