<?php

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/user/lib.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/lib/enrollib.php');
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$res = array();

foreach( json_decode(file_get_contents('php://stdin')) as $c ) {
  global $DB;
  $tr = array();
  try {
    if(empty($c->idnumber)) throw new moodle_exception('empty idnumber');

    $uid = $DB->get_record('user',array('idnumber'=>$c->idnumber));

    if($c->isa == 'NEW') {
      if(!$uid) {
        $uid = user_create_user($c);
      } else {
        $uid = $uid->id;
      }

      if(!empty($c->course_id) && !empty($c->role)) {
        // we want to enrol
        $role = '';
        switch($c->role) {
        case "convenor":
          $role = "editingteacher";
          break;
        case "teacher":
          $role = "teacher";
          break;
        default:
          throw new moodle_exception('unknown role '.$c->role);
        }

        $role = $DB->get_record('role', array('shortname'=>$role));
        $r = enrol_try_internal_enrol($c->course_id, $uid, $role->id);
        if(!$r) throw new moodle_exception('enrol_internal gave us false');
      }
    } else if($c->isa == 'DELETE') {
      if($uid) { // if the user doesnt exist, their enrolments should have been wiped already
        if (!enrol_is_enabled('manual')) {
          throw new moodle_exception('manual enrolment not enabled?');
        }
        if (!$enrol = enrol_get_plugin('manual')) {
          throw new moodle_exception('manual enrolment plugin not found?');
        }
        if (!$instances = $DB->get_records('enrol', array('enrol'=>'manual', 'courseid'=>$c->course_id, 'status'=>ENROL_INSTANCE_ENABLED), 'sortorder,id ASC')) {
          throw new moodle_exception('no course? ' + $c->course_id);
        }
        $instance = reset($instances);
        $enrol->unenrol_user($instance, $uid->id);
      }
    } else {
      throw new moodle_exception('dont understand ' + $c->isa);
    }

    $tr = array( 'result' => 'ok', 'id' => $uid, 'idnumber' => $c->idnumber );
  } catch( Exception $e ) {
    $tr = array(
      'result' => 'error',
      'idnumber' => "$c->idnumber",
      'exception' => $e->getMessage() );
  }
  $res []= $tr;
}

echo json_encode($res);
