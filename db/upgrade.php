<?php

defined('MOODLE_INTERNAL') || die;

function xmldb_local_connect_upgrade($oldversion) {
	global $CFG, $DB, $CONNECTDB;

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
		$field = new xmldb_field('locked_change_by', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'locked_change_at');
		$dbman->add_field($table, $field);

		upgrade_plugin_savepoint(true, 2012052913, 'local', 'connect');
	}

    if ($oldversion < 2014010901) {
        // Define table connect_course_chksum to be created.
        $table = new xmldb_table('connect_course_chksum');

        // Adding fields to table connect_course_chksum.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('module_delivery_key', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $table->add_field('session_code', XMLDB_TYPE_CHAR, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('chksum', XMLDB_TYPE_CHAR, '36', null, null, null, null);

        // Adding keys to table connect_course_chksum.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('course_unique', XMLDB_KEY_UNIQUE, array('courseid'));
        $table->add_key('sdsuid_unique', XMLDB_KEY_UNIQUE, array('module_delivery_key', 'session_code'));

        // Conditionally launch create table for connect_course_chksum.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014010901, 'local', 'connect');
    }

    if ($oldversion < 2014012000) {
        // Define table connect_enrolment_chksum to be created.
        $table = new xmldb_table('connect_enrolment_chksum');

        // Adding fields to table connect_enrolment_chksum.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('moodleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('chksum', XMLDB_TYPE_CHAR, '36', null, null, null, null);

        // Adding keys to table connect_enrolment_chksum.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for connect_enrolment_chksum.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014012000, 'local', 'connect');
    }

    if ($oldversion < 2014031200) {
        // Connect Campus.
        {
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
        {
            // Define table connect_enrolments to be created.
            $table = new xmldb_table('connect_enrolments');

            // Adding fields to table connect_enrolments.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('course', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
            $table->add_field('user', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
            $table->add_field('role', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);

            // Adding indexes to table connect_enrolments.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_index('unique_course', XMLDB_INDEX_NOTUNIQUE, array('course'));
            $table->add_index('unique_user', XMLDB_INDEX_NOTUNIQUE, array('user'));

            // Conditionally launch create table.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Connect Groups.
        {
            // Define table connect_group to be created.
            $table = new xmldb_table('connect_group');

            // Adding fields to table connect_group.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('course', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

            // Adding indexes to table connect_group.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_index('unique_course', XMLDB_INDEX_NOTUNIQUE, array('course'));

            // Conditionally launch create table.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Connect Group Enrolments.
        {
            // Define table connect_group_enrolments to be created.
            $table = new xmldb_table('connect_group_enrolments');

            // Adding fields to table connect_group_enrolments.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('group', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
            $table->add_field('user', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);

            // Adding indexes to table connect_group_enrolments.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_index('unique_group', XMLDB_INDEX_NOTUNIQUE, array('group'));
            $table->add_index('unique_user', XMLDB_INDEX_NOTUNIQUE, array('user'));

            // Conditionally launch create table.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Connect Roles.
        {
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
        {
            // Define table connect_user to be created.
            $table = new xmldb_table('connect_user');

            // Adding fields to table connect_user.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('ukc', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('login', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('initials', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('family_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

            // Adding keys to table connect_user.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('unique_login', XMLDB_KEY_UNIQUE, array('login'));

            // Conditionally launch create table.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Connect Courses.
        {
            // Define table connect_course to be created.
            $table = new xmldb_table('connect_course');

            // Adding fields to table connect_course.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
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
            $table->add_key('unique_module_delivery_key_session_code_module_version', XMLDB_KEY_UNIQUE, array('module_delivery_key', 'session_code', 'module_version'));

            // Adding indexes to table connect_course.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_index('unique_module_delivery_key_session_code', XMLDB_INDEX_NOTUNIQUE, array('module_delivery_key', 'session_code'));
            $table->add_index('unique_category', XMLDB_INDEX_NOTUNIQUE, array('category'));
            $table->add_index('unique_module_code', XMLDB_INDEX_NOTUNIQUE, array('module_code'));

            // Conditionally launch create table.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2014031200, 'local', 'connect');
    }

    return true;
}
