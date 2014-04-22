<?php

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/local/catman/lib.php');

// We need admin
\core\session\manager::set_user(get_admin());

$CFG->local_catman_enable = 1;
$CFG->local_catman_limit = 999999;

local_catman_cron();
