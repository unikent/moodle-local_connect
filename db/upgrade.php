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

defined('MOODLE_INTERNAL') || die;

function xmldb_local_connect_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012052912) {
        $table = new xmldb_table('connect_course_dets');

        $field = new xmldb_field('id');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                                XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->addField($field);

        $field = new xmldb_field('course');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                                XMLDB_NOTNULL, null, null, null);
        $table->addField($field);

        $field = new xmldb_field('campus');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null,
                                null, null, '', null);
        $table->addField($field);

        $field = new xmldb_field('startdate');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                                null, null, '0', null);
        $table->addField($field);

        $field = new xmldb_field('enddate');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                                null, null, '0', null);
        $table->addField($field);

        $field = new xmldb_field('weeks');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED,
                                null, null, '0', null);
        $table->addField($field);

        $key = new xmldb_key('primary');
        $key->set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($key);

        $index = new xmldb_index('course');
        $index->set_attributes(XMLDB_INDEX_UNIQUE, array('course'));
        $table->addIndex($index);

        $dbman->create_table($table);

        upgrade_plugin_savepoint(true, 2012052912, 'local', 'connect');
    }

    if ($oldversion < 2012052913) {
        $table = new xmldb_table('connect_course_dets');
        $field = new xmldb_field('unlocked', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'weeks');
        $dbman->add_field($table, $field);

        $table = new xmldb_table('connect_course_dets');
        $field = new xmldb_field('locked_change_at', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0', 'unlocked');
        $dbman->add_field($table, $field);

        $table = new xmldb_table('connect_course_dets');
        $field = new xmldb_field('locked_change_by', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
            null, null, null, 'locked_change_at');
        $dbman->add_field($table, $field);

        upgrade_plugin_savepoint(true, 2012052913, 'local', 'connect');
    }

    if ($oldversion < 2014031201) {
        // Connect Campus.
        if (true) {
            // Define table connect_campus to be created.
            $table = new xmldb_table('connect_campus');

            // Adding fields to table connect_campus.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

            // Adding keys to table connect_campus.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('unique_name', XMLDB_KEY_UNIQUE, array('name'));

            // Conditionally launch create table.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Connect Enrolments.
        if (true) {
            // Define table connect_enrolments to be created.
            $table = new xmldb_table('connect_enrolments');

            // Adding fields to table connect_enrolments.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('mid', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);
            $table->add_field('course', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
            $table->add_field('user', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
            $table->add_field('role', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
            $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, null, null, 0);

            // Adding indexes to table connect_enrolments.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_index('index_mid', XMLDB_INDEX_NOTUNIQUE, array('mid'));
            $table->add_index('index_course', XMLDB_INDEX_NOTUNIQUE, array('course'));
            $table->add_index('index_user', XMLDB_INDEX_NOTUNIQUE, array('user'));

            // Conditionally launch create table.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Connect Groups.
        if (true) {
            // Define table connect_group to be created.
            $table = new xmldb_table('connect_group');

            // Adding fields to table connect_group.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('mid', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);
            $table->add_field('course', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

            // Adding indexes to table connect_group.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_index('index_mid', XMLDB_INDEX_NOTUNIQUE, array('mid'));
            $table->add_index('index_course', XMLDB_INDEX_NOTUNIQUE, array('course'));

            // Conditionally launch create table.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Connect Group Enrolments.
        if (true) {
            // Define table connect_group_enrolments to be created.
            $table = new xmldb_table('connect_group_enrolments');

            // Adding fields to table connect_group_enrolments.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('mid', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);
            $table->add_field('groupid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
            $table->add_field('user', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
            $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, null, null, 0);

            // Adding indexes to table connect_group_enrolments.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_index('index_mid', XMLDB_INDEX_NOTUNIQUE, array('mid'));
            $table->add_index('index_groupid', XMLDB_INDEX_NOTUNIQUE, array('groupid'));
            $table->add_index('index_user', XMLDB_INDEX_NOTUNIQUE, array('user'));

            // Conditionally launch create table.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Connect Roles.
        if (true) {
            // Define table connect_role to be created.
            $table = new xmldb_table('connect_role');

            // Adding fields to table connect_role.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

            // Adding keys to table connect_role.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('unique_name', XMLDB_KEY_UNIQUE, array('name'));

            // Conditionally launch create table.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Connect Users.
        if (true) {
            // Define table connect_user to be created.
            $table = new xmldb_table('connect_user');

            // Adding fields to table connect_user.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('mid', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);
            $table->add_field('ukc', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('login', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('initials', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('family_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

            // Adding keys to table connect_user.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('unique_login', XMLDB_KEY_UNIQUE, array('login'));
            $table->add_index('index_mid', XMLDB_INDEX_NOTUNIQUE, array('mid'));

            // Conditionally launch create table.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Connect Courses.
        if (true) {
            // Define table connect_course to be created.
            $table = new xmldb_table('connect_course');

            // Adding fields to table connect_course.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('mid', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);
            $table->add_field('module_delivery_key', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
            $table->add_field('session_code', XMLDB_TYPE_CHAR, '4', null, XMLDB_NOTNULL, null, null);
            $table->add_field('module_version', XMLDB_TYPE_CHAR, '4', null, XMLDB_NOTNULL, null, null);
            $table->add_field('campus', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
            $table->add_field('module_week_beginning', XMLDB_TYPE_CHAR, '4', null, XMLDB_NOTNULL, null, null);
            $table->add_field('module_length', XMLDB_TYPE_CHAR, '4', null, XMLDB_NOTNULL, null, null);
            $table->add_field('week_beginning_date', XMLDB_TYPE_DATETIME, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('module_title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('module_code', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('synopsis', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('category', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);

            // Adding keys to table connect_course.
            $table->add_key('unique_module_delivery_key_session_code_module_version', XMLDB_KEY_UNIQUE,
                array('module_delivery_key', 'session_code', 'module_version'));

            // Adding indexes to table connect_course.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_index('index_mid', XMLDB_INDEX_NOTUNIQUE, array('mid'));
            $table->add_index('index_module_delivery_key_session_code', XMLDB_INDEX_NOTUNIQUE,
                array('module_delivery_key', 'session_code'));
            $table->add_index('index_category', XMLDB_INDEX_NOTUNIQUE, array('category'));
            $table->add_index('index_module_code', XMLDB_INDEX_NOTUNIQUE, array('module_code'));

            // Conditionally launch create table.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014031201, 'local', 'connect');
    }

    if ($oldversion < 2014031300) {
        // Define table connect_course_links to be created.
        $table = new xmldb_table('connect_course_links');

        // Adding fields to table connect_course_links.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('parent', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('child', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table connect_course_links.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('parent_child_key', XMLDB_KEY_UNIQUE, array('parent', 'child'));

        // Adding indexes to table connect_course_links.
        $table->add_index('parent_idx', XMLDB_INDEX_NOTUNIQUE, array('parent'));
        $table->add_index('child_idx', XMLDB_INDEX_NOTUNIQUE, array('child'));

        // Conditionally launch create table for connect_course_links.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014031300, 'local', 'connect');
    }

    if ($oldversion < 2014031700) {
        if (true) {
            // Define table connect_course_chksum to be dropped.
            $table = new xmldb_table('connect_course_chksum');

            // Conditionally launch drop table for connect_course_chksum.
            if ($dbman->table_exists($table)) {
                $dbman->drop_table($table);
            }
        }

        if (true) {
            // Define table connect_enrolment_chksum to be dropped.
            $table = new xmldb_table('connect_enrolment_chksum');

            // Conditionally launch drop table for connect_enrolment_chksum.
            if ($dbman->table_exists($table)) {
                $dbman->drop_table($table);
            }
        }

        if (true) {
            // Define field mid to be dropped from connect_enrolments.
            $table = new xmldb_table('connect_enrolments');

            $index = new xmldb_index('index_mid', XMLDB_INDEX_NOTUNIQUE, array('mid'));
            // Conditionally launch drop index index_mid.
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }

            $field = new xmldb_field('mid');
            // Conditionally launch drop field mid.
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        if (true) {
            // Define field mid to be dropped from connect_group_enrolments.
            $table = new xmldb_table('connect_group_enrolments');

            $index = new xmldb_index('index_mid', XMLDB_INDEX_NOTUNIQUE, array('mid'));
            // Conditionally launch drop index index_mid.
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }

            $field = new xmldb_field('mid');
            // Conditionally launch drop field mid.
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014031700, 'local', 'connect');
    }

    if ($oldversion < 2014031800) {
        $remaps = array(
            "connect_enrolments" => array(
                "course" => new xmldb_field('course', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'id'),
                "user" => new xmldb_field('user', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'courseid'),
                "role" => new xmldb_field('role', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null, 'userid')
            ),
            "connect_group" => array(
                "course" => new xmldb_field('course', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'mid')
            ),
            "connect_group_enrolments" => array(
                "user" => new xmldb_field('user', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'groupid')
            ),
            "connect_course" => array(
                "campus" => new xmldb_field('campus', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null, 'module_version')
            ),
        );

        foreach ($remaps as $table => $cols) {
            $table = new xmldb_table($table);
            foreach ($cols as $col => $colobj) {
                // If there is an index, drop it.
                $index = new xmldb_index('index_' . $col, XMLDB_INDEX_NOTUNIQUE, array($col));
                $indexexists = $dbman->index_exists($table, $index);
                if ($indexexists) {
                    $dbman->drop_index($table, $index);
                }

                // Rename the field.
                $field = $colobj;
                if ($dbman->field_exists($table, $field)) {
                    $dbman->rename_field($table, $field, $col . 'id');
                }

                // Create a new index.
                if ($indexexists) {
                    $index = new xmldb_index('index_' . $col . 'id', XMLDB_INDEX_NOTUNIQUE, array($col . 'id'));
                    if (!$dbman->index_exists($table, $index)) {
                        $dbman->add_index($table, $index);
                    }
                }
            }
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014031800, 'local', 'connect');
    }

    if ($oldversion < 2014031801) {
        $table = new xmldb_table("connect_role");

        if (true) {
            $field = new xmldb_field('mid', XMLDB_TYPE_INTEGER, '11', null, null, null, '0', 'id');

            // Conditionally launch add field mid.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        if (true) {
            $index = new xmldb_index('index_mid', XMLDB_INDEX_NOTUNIQUE, array('mid'));

            // Conditionally launch add index index_mid.
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014031801, 'local', 'connect');
    }

    if ($oldversion < 2014050800) {
        // Define table connect_group to be dropped.
        $table = new xmldb_table('connect_course_links');

        // Conditionally launch drop table for connect_group.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table connect_rules to be created.
        $table = new xmldb_table('connect_rules');

        // Adding fields to table connect_rules.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('prefix', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, null);
        $table->add_field('category', XMLDB_TYPE_INTEGER, '11', null, null, null, '0');
        $table->add_field('weight', XMLDB_TYPE_INTEGER, '3', null, null, null, '50');

        // Adding keys to table connect_rules.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('prefix', XMLDB_KEY_UNIQUE, array('prefix'));

        // Adding indexes to table connect_rules.
        $table->add_index('index_category', XMLDB_INDEX_NOTUNIQUE, array('category'));

        // Conditionally launch create table for connect_rules.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014050800, 'local', 'connect');
    }

    if ($oldversion < 2014050900) {
        $DB->delete_records_select('connect_course', 'module_delivery_key LIKE "%-%"');

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014050900, 'local', 'connect');
    }

    if ($oldversion < 2014051200) {

        // Define table connect_meta to be created.
        $table = new xmldb_table('connect_meta');

        // Adding fields to table connect_meta.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('objectid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('objecttype', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table connect_meta.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table connect_meta.
        $table->add_index('i_object', XMLDB_INDEX_NOTUNIQUE, array('objectid', 'objecttype'));
        $table->add_index('i_courseid', XMLDB_INDEX_NOTUNIQUE, array('courseid'));

        // Conditionally launch create table for connect_meta.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014051200, 'local', 'connect');
    }

    if ($oldversion < 2014051600) {
        // Define table connect_course_locks to be created.
        $table = new xmldb_table('connect_course_locks');

        // Adding fields to table connect_course_locks.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('mid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('locked', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');

        // Adding keys to table connect_course_locks.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('i_mid', XMLDB_KEY_UNIQUE, array('mid'));

        // Conditionally launch create table for connect_course_locks.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Now update locks.
        $DB->execute("INSERT INTO {connect_course_locks} (mid, locked)
            (SELECT DISTINCT course, CASE WHEN unlocked = 0 THEN 1 ELSE 0 END FROM {connect_course_dets})");

        // Define table connect_course_dets to be dropped.
        $table = new xmldb_table('connect_course_dets');

        // Conditionally launch drop table for connect_course_dets.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014051600, 'local', 'connect');
    }

    if ($oldversion < 2014052100) {

        // Define table connect_timetabling to be created.
        $table = new xmldb_table('connect_timetabling');

        // Adding fields to table connect_timetabling.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('eventid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('typeid', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('roomid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('starts', XMLDB_TYPE_CHAR, '5', null, null, null, '0900');
        $table->add_field('ends', XMLDB_TYPE_CHAR, '5', null, null, null, '1000');
        $table->add_field('day', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('weeks', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table connect_timetabling.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table connect_timetabling.
        $table->add_index('i_eventid', XMLDB_INDEX_NOTUNIQUE, array('eventid'));
        $table->add_index('i_typeid', XMLDB_INDEX_NOTUNIQUE, array('typeid'));
        $table->add_index('i_userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('i_courseid', XMLDB_INDEX_NOTUNIQUE, array('courseid'));

        // Conditionally launch create table for connect_timetabling.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table connect_type to be created.
        $table = new xmldb_table('connect_type');

        // Adding fields to table connect_type.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table connect_type.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('k_name', XMLDB_KEY_UNIQUE, array('name'));

        // Conditionally launch create table for connect_type.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table connect_room to be created.
        $table = new xmldb_table('connect_room');

        // Adding fields to table connect_room.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('campusid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table connect_room.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('k_name_campusid', XMLDB_KEY_UNIQUE, array('name', 'campusid'));

        // Adding indexes to table connect_room.
        $table->add_index('i_campusid', XMLDB_INDEX_NOTUNIQUE, array('campusid'));
        $table->add_index('i_name', XMLDB_INDEX_NOTUNIQUE, array('name'));

        // Conditionally launch create table for connect_room.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014052100, 'local', 'connect');
    }

    if ($oldversion < 2014052200) {
        // Define table connect_weeks to be created.
        $table = new xmldb_table('connect_weeks');

        // Adding fields to table connect_weeks.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('week_beginning', XMLDB_TYPE_CHAR, '21', null, XMLDB_NOTNULL, null, null);
        $table->add_field('week_beginning_date', XMLDB_TYPE_DATETIME, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('week_number', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');

        // Adding keys to table connect_weeks.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('k_week_beginning', XMLDB_KEY_UNIQUE, array('week_beginning'));

        // Conditionally launch create table for connect_weeks.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014052200, 'local', 'connect');
    }

    if ($oldversion < 2014061600) {
        // Define field shortname_ext to be added to connect_course.
        $table = new xmldb_table('connect_course');
        $field = new xmldb_field('shortname_ext', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'category');

        // Conditionally launch add field shortname_ext.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014061600, 'local', 'connect');
    }

    if ($oldversion < 2014061601) {
        // Define field shortname_ext to be dropped from connect_course.
        $table = new xmldb_table('connect_course');
        $field = new xmldb_field('shortname_ext');

        // Conditionally launch drop field shortname_ext.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        unset($table);
        unset($field);

        // Define table connect_course_exts to be created.
        $table = new xmldb_table('connect_course_exts');

        // Adding fields to table connect_course_exts.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('coursemid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('extension', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table connect_course_exts.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('k_coursemid', XMLDB_KEY_UNIQUE, array('coursemid'));

        // Conditionally launch create table for connect_course_exts.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014061601, 'local', 'connect');
    }

    if ($oldversion < 2014062700) {
        $category = \local_catman\core::get_category();

        $courses = $DB->get_records('course', array(
            'category' => $category->id
        ));

        foreach ($courses as $course) {
            $DB->set_field('connect_course', 'mid', 0, array(
                'mid' => $course->id
            ));
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014062700, 'local', 'connect');
    }

    if ($oldversion < 2014092100) {
        // Define index i_week_beginning_date (not unique) to be added to connect_weeks.
        $table = new xmldb_table('connect_weeks');
        $index = new xmldb_index('i_week_beginning_date', XMLDB_INDEX_NOTUNIQUE, array('week_beginning_date'));

        // Conditionally launch add index i_week_beginning_date.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014092100, 'local', 'connect');
    }

    if ($oldversion < 2014092101) {
        // Define index i_week_number (not unique) to be added to connect_weeks.
        $table = new xmldb_table('connect_weeks');
        $index = new xmldb_index('i_week_number', XMLDB_INDEX_NOTUNIQUE, array('week_number'));

        // Conditionally launch add index i_week_number.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014092101, 'local', 'connect');
    }

    if ($oldversion < 2014092600) {
        $table = new xmldb_table('connect_enrolments');

        // Define field deleted to be dropped from connect_enrolments.
        $field = new xmldb_field('deleted');

        // Conditionally launch drop field deleted.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014092600, 'local', 'connect');
    }

    if ($oldversion < 2014100101) {
        $table = new xmldb_table('connect_course');

        // Define field deleted to be dropped from connect_course.
        $field = new xmldb_field('shortname_ext');

        // Conditionally launch drop field deleted.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014100101, 'local', 'connect');
    }

    if ($oldversion < 2014100102) {
        $table = new xmldb_table('connect_course');
        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, null, null, 0, 'category');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014100102, 'local', 'connect');
    }

    if ($oldversion < 2015010500) {
        $table = new xmldb_table('connect_meta');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2015010500, 'local', 'connect');
    }

    if ($oldversion < 2015010700) {
        // Delete all locks.
        $DB->delete_records('connect_course_locks');

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2015010700, 'local', 'connect');
    }

    if ($oldversion < 2015011401) {
        $roles = $DB->get_records('connect_role');
        foreach ($roles as $role) {
            // Re-map any new sds_ ones to old.
            if (substr($role->name, 0, 4) == 'sds_') {
                $oldname = substr($role->name, 4);
                $oldrole = $DB->get_record('connect_role', array(
                    'name' => $oldname
                ));
                if ($oldrole) {
                    $DB->execute("UPDATE {connect_enrolments} SET roleid=:newid WHERE roleid=:oldid", array(
                        'newid' => $oldrole->id,
                        'oldid' => $role->id
                    ));
                    
                    // Delete the "new" one.
                    $DB->delete_records('connect_role', array(
                        'id' => $role->id
                    ));
                }
            }
        }

        // Now rename.
        $roles = $DB->get_records('connect_role');
        foreach ($roles as $role) {
            if (substr($role->name, 0, 4) != 'sds_') {
                $role->name = 'sds_' . $role->name;
                $DB->update_record('connect_role', $role);
            }
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2015011401, 'local', 'connect');
    }

    return true;
}
