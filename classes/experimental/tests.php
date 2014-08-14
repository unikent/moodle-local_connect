<?php

require_once('../../../../config.php');

$obj = new \local_connect\experimental\SDS\enrolments();
print_r($obj->get_all_teachers(2015));