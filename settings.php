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

if ($hassiteconfig) {
    $ADMIN->add('reports', new admin_externalpage('reportconnectreport', get_string('connectreport', 'local_connect'),
        "$CFG->wwwroot/local/connect/index.php", 'local/connect:manage'));

    $settings = new admin_settingpage('local_connect', get_string('pluginname', 'local_connect'));
    $ADMIN->add('localplugins', $settings);

    $cdb = new admin_externalpage('connectdatabrowse', "Connect Data Browser", "$CFG->wwwroot/local/connect/browse/index.php",
        'local/connect:helpdesk');
    $ADMIN->add('localplugins', $cdb);

    $settings->add(new admin_setting_configcheckbox(
        'local_connect_enable',
        get_string('enable', 'local_connect'),
        '',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_connect/enable_sds_sync',
        'Enable SDS sync',
        '',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_connect/enable_sits_sync',
        'Enable SITS sync',
        '',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_connect/enable_course_sync',
        'Enable course syncing',
        'Allows modules to update their description from their data source.',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_connect/strict_sync',
        'Force strict sync',
        'Forces modules to update their data, rather than letting convenors modify them Moodle-side.',
        0
    ));
}
