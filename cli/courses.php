<?php

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require(dirname(dirname(dirname(dirname(__FILE__)))).'/course/lib.php');
require(dirname(dirname(dirname(dirname(__FILE__)))).'/course/edit_form.php');
require(dirname(dirname(__FILE__)).'/locallib.php');

function kent_connect_fetch_or_create_removed_category_id() {
  global $DB;
  $category = $DB->get_record('course_categories', array('idnumber' => 'kent_connect_removed'));

  if(!$category) {
    $category = new stdClass();
    $category->name = 'Removed';
    $category->idnumber = 'kent_connect_removed';
    $category->description_editor = $data->description_editor;
    $category->parent = 0;
    $category->description = 'Holding place for removed courses';
    $category->sortorder = 999;
    $category->visible = false;
    $category->id = $DB->insert_record('course_categories', $category);
  }

  return $category->id;
}

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
      global $DB;
      $r = $DB->get_record('course',array('idnumber'=>$c->idnumber));
      if(!$r) {
        throw new moodle_exception('course doesnt exist');
      }
      $r->category = kent_connect_fetch_or_create_removed_category_id();
      update_course($r);
      $tr = array( 'result' => 'ok', 'in' => $c );
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
