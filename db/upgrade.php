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

defined('MOODLE_INTERNAL') || die;

function xmldb_local_connect_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016042900) {
        // Define field synopsis to be dropped from connect_course.
        $table = new xmldb_table('connect_course');
        $field = new xmldb_field('synopsis');

        // Conditionally launch drop field synopsis.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define table course_handbook to be created.
        $table = new xmldb_table('connect_course_handbook');

        // Adding fields to table course_handbook.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('module_code', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('synopsis', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('publicationssynopsis', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('contacthours', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('learningoutcome', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('methodofassessment', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('preliminaryreading', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('updateddate', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        $table->add_field('availability', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('cost', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('prerequisites', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('progression', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('restrictions', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table course_handbook.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('k_module_code', XMLDB_KEY_UNIQUE, array('module_code'));

        // Conditionally launch create table for course_handbook.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2016042900, 'local', 'connect');
    }

    return true;
}
