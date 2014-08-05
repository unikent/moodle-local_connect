<?php

require_once('../../../../config.php');

$objs = \local_connect\experimental\SDS\enrolments::get_all_teachers(2015);
print_r(($objs));