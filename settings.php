<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('reports', new admin_externalpage('reportconnectreport', get_string('connectreport', 'local_connect'), "$CFG->wwwroot/local/connect/index.php", 'local/kentconnect:manage'));

	$settings = new admin_settingpage('local_connect', get_string('pluginname', 'local_connect'));
	$ADMIN->add('localplugins', $settings);

	$settings->add(new admin_setting_configcheckbox(
		'local_connect_enable',
		get_string('enable', 'local_connect'),
		'',
		0
	));

	$settings->add(new admin_setting_configcheckbox(
		'local_connect_enable_new_features',
		get_string('new_feature_toggle', 'local_connect'),
		get_string('new_feature_toggle_desc', 'local_connect'),
		0
	));

	$settings->add(new admin_setting_configcheckbox(
		'local_connect_enable_observers',
		get_string('observer_toggle', 'local_connect'),
		get_string('observer_toggle_desc', 'local_connect'),
		0
	));

	$settings->add(new admin_setting_configcheckbox(
		'local_connect_enable_sharedb',
		get_string('sharedb_toggle', 'local_connect'),
		get_string('sharedb_toggle_desc', 'local_connect'),
		0
	));

	$settings->add(new admin_setting_configcheckbox(
		'local_connect_enable_cron',
		get_string('cron_toggle', 'local_connect'),
		get_string('cron_toggle_desc', 'local_connect'),
		0
	));
}
