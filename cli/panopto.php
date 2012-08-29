<?php
define('CLI_SCRIPT',true);

require_once 'config.php';
require_once dirname(__FILE__).'/../../../blocks/panopto/lib/panopto_data.php';

global $CFG, $USER;
/*
$CFG->block_panopto_instance_name = 'Moodle';
$CFG->block_panopto_server_name = 'kent.hosted.panopto.com';
$CFG->block_panopto_application_key = 'ad02dbb7-6a73-4b3b-87bd-e3f693185185';
 */
$USER->username = 'moodlesync';

$result = array();

foreach( json_decode(file_get_contents('php://stdin')) as $c ) {
  try {
    $panopto_data = new panopto_data(null);
    $panopto_data->moodle_course_id = $c;
    $provisioning_data = $panopto_data->get_provisioning_info();
    $provisioned_data = $panopto_data->provision_course($provisioning_data);

    $result []= array(
      'result' => 'ok',
      'in' => $c,
      'out' => $provisioned_data);
  } catch( Exception $e ) {
    $result []= array(
      'result' => 'error',
      'in' => $c,
      'exception' => $e->getMessage());
  }
}

echo json_encode($result);

