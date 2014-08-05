<?php

require_once('../../../../config.php');

$objs = \local_connect\experimental\SDS\group_enrolments::get_all(2014);
print_r(count($objs));