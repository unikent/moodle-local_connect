<?php

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/user/lib.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/group/lib.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/lib/accesslib.php');
require_once(dirname(dirname(__FILE__)).'/locallib.php');

$res = array();

/*
 * expects input
 * [
 *  { 'group_desc' => 'something', 'moodle_course_id' => 321
 *    'isa' => 'NEW' or 'DELETE', 'moodle_group_id' => 12312 or nil
 *    'group_id' => 354 }
 *  ...
 * ]
 *
 * sends output :
 * [
 *   { 'result' => 'ok' or 'error', 'exception' => if error
 *     'moodle_group_id' => 345, 'in' => input }
 * ]
 */

foreach( json_decode(file_get_contents('php://stdin')) as $c ) {
  global $DB;
  $tr = array();
  $group = (object) array();
  try {

    if($c->isa == 'NEW') {

      $data = (object) array( 'name' => $c->group_desc, 'courseid' => $c->moodle_course_id );
      $group = groups_create_group($data);
      $c->moodle_group_id = $group;
      if(! ($grouping = $DB->get_record('groupings',array('name'=>'Delivery groups','courseid'=>$c->moodle_course_id))) ) {
        $data->name = "Delivery groups";
        $grouping = groups_create_grouping($data);
      } else {
        $grouping = $grouping->id;
      }
      if(!($g = $DB->get_record('groupings_groups',array('groupid'=>$group,'groupingid'=>$grouping))) ) {
        groups_assign_grouping($grouping, $group);
      }

    } else if($c->isa == 'DELETE') {
      if( !groups_delete_group($c->moodle_group_id) ) {
        throw new moodle_exception('groups_delete_group failed');
      }
    } else {
      throw new moodle_exception('dont understand ' + $c->isa);
    }

    $tr = array( 'result' => 'ok', 'moodle_group_id' => $group->id, 'in' => $c );
  } catch( Exception $e ) {
    $tr = array(
      'result' => 'error',
      'in' => $c,
      'exception' => $e );
  }
  $res []= $tr;
}

echo json_encode($res);
