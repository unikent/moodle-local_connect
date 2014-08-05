<?php

require_once('../../../../config.php');

$obj = new \local_connect\experimental\SDS\course();
$obj->sync();