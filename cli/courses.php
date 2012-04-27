<?php

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require(dirname(dirname(dirname(dirname(__FILE__)))).'/course/lib.php');
require(dirname(dirname(dirname(dirname(__FILE__)))).'/course/edit_form.php');
require(dirname(dirname(__FILE__)).'/locallib.php');

$res = array();

/*
 * expects input :
 * [
 *  { shortname => "", fullname => "",
 *    category => 1, visible => 1, idnumber => "",
 *    startdate => '', isa => "NEW" }
 *  ...
 * ]
 *
 * sends output :
 * [
 *   { 'result' => 'ok' or 'error', 'exception' => if error
 *     moodle_course_id => 123, 'in' => input }
 * ]
 */

foreach( json_decode(file_get_contents('php://stdin')) as $c ) {
  $tr = array();
  try {
    if(empty($c->idnumber)) throw new moodle_exception('empty idnumber');
    $c->visible = 0;
    if($c->isa == 'NEW' ) {
      global $DB;
      $r = $DB->get_record('course',array('idnumber'=>$c->idnumber));
      if($r) {
        throw new moodle_exception('non unique idnumber');
      }

      // create one section for each duration
      $c->numsections = $c->duration != null ? $c->duration : 1;
      $c->maxbytes = '10485760';
      $cr = create_course($c);

      $DB->set_field('course_sections', 'name', $c->fullname, array('course'=>$cr->id, 'section'=>0));

      // gives our course a news forum, which means modinfo
      // can get populated and we dont have to refresh to see courses..
      require_once($CFG->dirroot .'/mod/forum/lib.php');
      forum_get_course_forum($cr->id,'news');

      $tr = array( 'result' => 'ok', 'moodle_course_id' => $cr->id, 'in' => $c );
    } else if($c->isa == 'DELETE') {
      throw new moodle_exception('delete not implemented for courses');
    } else {
      throw new moodle_exception('dont understand '.$c->isa);
    }
  } catch( Exception $e ) {
    $tr = array(
      'result' => 'error',
      'in' => $c,
      'exception' => $e->getMessage() );
  }
  $res []= $tr;
}

echo json_encode($res);
