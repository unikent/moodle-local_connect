<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('reports', new admin_externalpage('reportconnectreport', get_string('connectreport', 'local_connect'), "$CFG->wwwroot/local/connect/index.php", 'local/kentconnect:manage'));
    $ADMIN->add('reports', new admin_externalpage('reportconnectsharedreport', get_string('sharedreport', 'local_connect'), "$CFG->wwwroot/local/connect/sharedreport.php", 'local/kentconnect:manage'));

	$settings = new admin_settingpage('local_connect', get_string('pluginname', 'local_connect'));
	$ADMIN->add('localplugins', $settings);

	$rules = new admin_externalpage('connectrules', "Category Rules", "$CFG->wwwroot/local/connect/rules.php", 'moodle/site:config');
	$ADMIN->add('localplugins', $rules);

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
		'local_connect_enable_sharedb',
		get_string('sharedb_toggle', 'local_connect'),
		"This is currently experimental though it appears to be stable. Be careful!",
		0
	));

	$settings->add(new admin_setting_configcheckbox(
		'local_connect_enable_cron',
		get_string('cron_toggle', 'local_connect'),
		get_string('cron_toggle_desc', 'local_connect'),
		0
	));

	$settings->add(new admin_setting_configcheckbox(
		'local_connect_enable_rollover',
		"Enable new-style rollover",
		"Warning: Rollover may not work after checking this! SHAREDB must be enabled for this to have any hope of working.",
		0
	));

	$settings->add(new admin_setting_configcheckbox(
		'local_connect/strict_sync',
		'Enable stricter SDS sync',
		'Forces modules to update to SDS data, rather than letting convenors modify them Moodle-side.',
		0
	));

	$settings->add(new admin_setting_configcheckbox(
		'local_connect/enable_hipchat',
		'Enable hipchat notifications',
		'Note: Spams the developers when things go wrong.',
		0
	));
}
