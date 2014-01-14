<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('reports', new admin_externalpage('reportconnectreport', get_string('connectreport', 'local_connect'), "$CFG->wwwroot/local/connect/index.php", 'local/kentconnect:manage'));

	$settings = new admin_settingpage('local_connect', get_string('pluginname', 'local_connect'));
	$ADMIN->add('localplugins', $settings);

	$setting = new admin_setting_configcheckbox('local_connect_enable_new_features', get_string('local_connect_new_feature_toggle', 'local_connect'), '', 0);
	$settings->add($setting);
}
