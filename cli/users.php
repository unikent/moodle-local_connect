<?php

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/user/lib.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/lib/enrollib.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/admin/roles/lib.php');
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$res = array();

/*
 * expects input :
 * [
 *   { username => "", password => '',
 *     firstname => "", lastname => "",
 *     email => "", auth => 'manual', isa => "NEW" 
 *     role => "convenor|teacher|student", moodle_course_id => "" }
 *  ...
 * ]
 *
 * sends output :
 * [
 *   { 'result' => 'ok' or 'error', 'exception' => if error
 *     moodle_user_id => 123, 'in' => input }
 * ]
 */

foreach( json_decode(file_get_contents('php://stdin')) as $c ) {
  global $DB;
  $tr = array();
  try {
    if(empty($c->username)) throw new moodle_exception('empty username');

    $uid = $DB->get_record('user',array('username'=>$c->username));

    if($c->isa == 'NEW') {
      if(!$uid) {
        $uid = user_create_user($c);
      } else {
        $uid = $uid->id;
      }

      if(!empty($c->moodle_course_id) && !empty($c->role)) {
        // we want to enrol
        $shortname = '';
        switch($c->role) {
        case "convenor":
          $shortname = "convenor";
          $name = "Convenor";
          $parent_id = 3;
          break;
        case "teacher":
          $shortname = "sds_teacher";
          $name = "Teacher (sds)";
          $parent_id = 3;
          break;
        case "student":
          $shortname = "sds_student";
          $name = "Student (sds)";
          $parent_id = 5;
          break;
        default:
          throw new moodle_exception('unknown role '.$c->role);
        }

        $role = $DB->get_record('role', array('shortname'=>$shortname));
        
        if(empty($role)) {
          unset($_POST['name']);
          unset($_POST['shortname']);
          $systemcontext = get_context_instance(CONTEXT_SYSTEM);
          $definitiontable = new define_role_table_basic($systemcontext, $parent_id);
          $definitiontable->read_submitted_permissions();
          $definitiontable->make_copy();
          $_POST['name'] = $name;
          $_POST['shortname'] = $shortname;
          $definitiontable->read_submitted_permissions();
          $definitiontable->save_changes();

          $role = $DB->get_record('role', array('shortname'=>$shortname));

        }


        $r = enrol_try_internal_enrol($c->moodle_course_id, $uid, $role->id);
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
        if (!$instances = $DB->get_records('enrol', array('enrol'=>'manual', 'courseid'=>$c->moodle_course_id, 'status'=>ENROL_INSTANCE_ENABLED), 'sortorder,id ASC')) {
          throw new moodle_exception('no course? ' + $c->moodle_course_id);
        }
        $instance = reset($instances);
        $enrol->unenrol_user($instance, $uid->id);
      }
    } else {
      throw new moodle_exception('dont understand ' + $c->isa);
    }

    $tr = array( 'result' => 'ok', 'moodle_user_id' => $uid, 'username' => $c->username, 'in' => $c );
  } catch( Exception $e ) {
    $tr = array(
      'result' => 'error',
      'in' => $c,
      'exception' => $e->getMessage() );
  }
  $res []= $tr;
}

echo json_encode($res);
