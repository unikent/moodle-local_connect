<?php

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require(dirname(dirname(dirname(dirname(__FILE__)))).'/course/lib.php');
require(dirname(dirname(dirname(dirname(__FILE__)))).'/course/edit_form.php');
require(dirname(dirname(dirname(dirname(__FILE__)))).'/mod/aspirelists/lib.php');
require(dirname(dirname(__FILE__)).'/locallib.php');

function kent_connect_fetch_or_create_removed_category_id() {
  global $DB, $CFG;
  $category = $DB->get_record('course_categories', array('idnumber' => 'kent_connect_removed'));

  if(!$category) {
    $category = new stdClass();
    $category->name = 'Removed';
    $category->idnumber = 'kent_connect_removed';
    $category->description_editor = $data->description_editor;
    $category->parent = 0;
    $category->description = 'Holding place for removed modules';
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
      $c->numsections = $c->module_length != null ? $c->module_length : 1;
      $c->maxbytes = '10485760';
      $cr = create_course($c);

      $DB->set_field('course_sections', 'name', $c->fullname, array('course'=>$cr->id, 'section'=>0));

      // Add module extra details to the connect_course_dets table
      $connect_data = new stdClass;
      $connect_data->course = $cr->id;
      $connect_data->campus = isset($c->campus_desc) ? $c->campus_desc : '';
      $connect_data->startdate = isset($c->startdate) ? $c->startdate : '';
      $connect_data->enddate = isset($c->module_length) ? strtotime('+'. $c->module_length .' weeks', $c->startdate) : $CFG->default_course_end_date;
      $connect_data->weeks = isset($c->module_length) ? $c->module_length : 0;

      $DB->insert_record('connect_course_dets', $connect_data);

      //Add the reading list module to our course if it is based in Canterbury
      if($connect_data->campus === 'Canterbury') {

        $module = $DB->get_record('modules', array('name'=>'aspirelists'));

        $rl = new stdClass();
        $rl->course     = $cr->id;
        $rl->name       = 'Reading list';
        $rl->intro      = '';
        $rl->introformat  = 1;
        $rl->category     = 'all';
        $rl->timemodified = time();

        $instance = aspirelists_add_instance($rl, new stdClass());

        $sql = "SELECT id, sequence FROM {$CFG->prefix}course_sections 
                WHERE course = {$cr->id} 
                  AND section = 0";

        $section = $DB->get_record_sql($sql);

        $cm = new stdClass();
        $cm->course     = $cr->id;
        $cm->module     = $module->id;
        $cm->instance     = $instance;
        $cm->section    = $section->id;
        $cm->visible    = 1;

        $cm->coursemodule = add_course_module($cm);

        $sequence = "$cm->coursemodule,$section->sequence";

        $DB->set_field('course_sections', 'sequence', $sequence, array('id'=>$section->id));
      }
      // gives our module a news forum, which means modinfo
      // can get populated and we dont have to refresh to see modules..
      require_once($CFG->dirroot .'/mod/forum/lib.php');
      forum_get_course_forum($cr->id,'news');

      // enable guest access for this course
      $enrol = $DB->get_record('enrol', array('enrol' => 'guest', 'courseid' => $cr->id));

      if ($enrol) {
        // set status to 0.. no, I don't know why '0' means guest is enabled, and '1'
        // means it's disabled... it starts off as 1 - what the fuck
        $edata = new stdClass;
        $edata->id = $enrol->id;
        $edata->status = 0;
        $DB->update_record('enrol', $edata);
      } else {
        // enrol doesn't exist... not sure this should happen but if it does we
        // can probably insert a new guest enrol set to 0?
      }

      $tr = array( 'result' => 'ok', 'moodle_course_id' => $cr->id, 'in' => $c );
    } else if($c->isa == 'UPDATE') {
      global $DB;
      $r = $DB->get_record('course',array('id'=>$c->moodle_id));
      if(!$r) {
        throw new moodle_exception('module doesnt exist');
      }

      // update module extra details too
      $connect_data = $DB->get_record('connect_course_dets',array('course'=>$r->id));
      if(!$connect_data) {
        $connect_data = new stdClass;
        $connect_data->course = $r->id;
        $connect_data->campus = isset($c->campus_desc) ? $c->campus_desc : '';
        $connect_data->startdate = isset($c->startdate) ? $c->startdate : '';
        $connect_data->enddate = isset($c->module_length) ? strtotime('+'. $c->module_length .' weeks', $c->startdate) : $CFG->default_course_end_date;
        $connect_data->weeks = isset($c->module_length) ? $c->module_length : 0;
        $DB->insert_record('connect_course_dets', $connect_data);
        $c->visible = $r->visible;
        $uc = (object)array_merge((array)$r,(array)$c );
        update_course( $uc );
      } else if(!$connect_data->unlocked) {
        $connect_data->campus = isset($c->campus_desc) ? $c->campus_desc : '';
        $connect_data->startdate = isset($c->startdate) ? $c->startdate : '';
        $connect_data->enddate = isset($c->module_length) ? strtotime('+'. $c->module_length .' weeks', $c->startdate) : $CFG->default_course_end_date;
        $connect_data->weeks = isset($c->module_length) ? $c->module_length : 0;
        $DB->update_record('connect_course_dets', $connect_data);
        $c->visible = $r->visible;
        $uc = (object)array_merge((array)$r,(array)$c );
        update_course( $uc );
      } else {
        // locked
      }

      $tr = array( 'result' => 'ok', 'in' => $c );
    } else if($c->isa == 'DELETE') {
      global $DB;
      $r = $DB->get_record('course',array('idnumber'=>$c->idnumber));
      if(!$r) {
        throw new moodle_exception('module doesnt exist');
      }
      $r->category = kent_connect_fetch_or_create_removed_category_id();

      //Update the shortcode before moving into the removed category
      $r->shortname = date("dmY-His") . "-" . $r->shortname;
      $r->idnumber = date("dmY-His") . "-" . $r->idnumber;
      update_course($r);
      $tr = array( 'result' => 'ok', 'in' => $c );
    } else {
      throw new moodle_exception('dont understand '.$c->isa);
    }
  } catch( Exception $e ) {

    var_dump($e);
    $tr = array(
      'result' => 'error',
      'in' => $c,
      'exception' => $e->getMessage() );
  }
  $res []= $tr;
}

echo json_encode($res);
