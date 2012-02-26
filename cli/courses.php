<?php

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require(dirname(dirname(dirname(dirname(__FILE__)))).'/course/lib.php');
require(dirname(dirname(__FILE__)).'/locallib.php');

$res = array();

foreach( json_decode(file_get_contents('php://stdin')) as $c ) {
  $tr = array();
  try {
    if(empty($c->idnumber)) throw new moodle_exception('empty idnumber');

    if($c->isa == 'NEW' ) {
      $cr = create_course($c);
      $tr = array( 'result' => 'ok', 'id' => $cr->id, 'idnumber' => $c->idnumber );
    } else if($c->isa == 'DELETE') {
      throw new moodle_exception('delete not implemented for courses');
    } else {
      throw new moodle_exception('dont understand '.$c->isa);
    }
  } catch( Exception $e ) {
    $tr = array(
      'result' => 'error',
      'idnumber' => "$c->idnumber",
      'exception' => $e->getMessage() );
  }
  $res []= $tr;
}

echo json_encode($res);
