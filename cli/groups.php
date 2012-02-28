<?php

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/user/lib.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/group/lib.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/lib/accesslib.php');
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$res = array();

/*
 * expects input : (needs user fields in here too)
 * [
 *  { 'moodle_user_id' => 123, 'group_desc' => 'something', 'moodle_course_id' => 321
 *    'isa' => 'NEW' or 'DELETE'
 *    'moodle_group_id' => 354 or nil }
 *  ...
 * ]
 *
 * sends output :
 * [
 *   { 'result' => 'ok' or 'error', 'exception' => if error
 *     'moodle_group_id' => 345, 'moodle_user_id' => 123, 'in' => input }
 * ]
 */

foreach( json_decode(file_get_contents('php://stdin')) as $c ) {
  global $DB;
  $tr = array();
  $group = (object) array();
  try {
    $uid = !empty($c->moodle_user_id) ? $DB->get_record('user',array('id'=>$c->moodle_user_id)) : false;

    if($c->isa == 'NEW') {
      if(!$uid) {
        $uid = user_create_user($c);
      } else {
        $uid = $uid->id;
      }

      if(empty($c->moodle_group_id)) {
        $data = (object) array( 'name' => $c->group_desc, 'courseid' => $c->moodle_course_id );
        $group = groups_create_group($data);
        $c->moodle_group_id = $group;
      }

      $group = $DB->get_record('groups',array('id'=>$c->moodle_group_id));
      $r = groups_add_member($group,$uid);
      if(!$r) {
        $reason = '';
        if( !is_enrolled(get_context_instance(CONTEXT_COURSE,$group->moodle_course_id), $uid) ) {
          $reason = 'user isnt enrolled on course '.$group->courseid;
        }
        throw new moodle_exception('group_add_member failed for '.$group->id.' and '.$uid.' '.$reason);
      }

    } else if($c->isa == 'DELETE') {
      if($uid) { // if the user doesnt exist, their enrolments should have been wiped already
        // for now just remove the user, not the group
        $group = $DB->get_record('groups',array('id'=>$c->moodle_group_id));
        if($group) { // cant find the group, must already not be there
          $r = groups_remove_member($group,$uid);
          if(!$r) {
            throw new moodle_exception('group_remove_member failed for '.$group->id.' and '.$uid);
          }
        }
      }
    } else {
      throw new moodle_exception('dont understand ' + $c->isa);
    }

    $tr = array( 'result' => 'ok', 'moodle_group_id' => $group->id, 'moodle_user_id' => $uid, 'in' => $c );
  } catch( Exception $e ) {
    $tr = array(
      'result' => 'error',
      'in' => $c,
      'exception' => $e );
  }
  $res []= $tr;
}

echo json_encode($res);
