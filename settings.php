<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('reports', new admin_externalpage('reportconnectreport', get_string('connectreport', 'local_connect'), "$CFG->wwwroot/local/connect/index.php", 'local/kent-connect:manage'));
}