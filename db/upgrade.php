<?php

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

	if($oldversion < 2012052913) {

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
}