<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2014031700;
$plugin->requires  = 2013110500;
$plugin->cron      = 0;

$plugin->dependencies = array(
    'local_catman' => 2014022600
);