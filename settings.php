<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('reports', new admin_externalpage('reportconnectreport', get_string('connectreport', 'local_connect'), "$CFG->wwwroot/local/connect/index.php", 'local/kentconnect:manage'));

	$settings->add(new admin_setting_configcheckbox(
		'local_connect_enable_new_features',
		get_string('local_connect_new_feature_toggle', 'local_connect'),
		'', false
	));
}