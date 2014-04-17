<?php

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require(dirname(dirname(dirname(dirname(__FILE__)))).'/course/lib.php');
require(dirname(dirname(dirname(dirname(__FILE__)))).'/course/edit_form.php');
require(dirname(dirname(dirname(dirname(__FILE__)))).'/mod/aspirelists/lib.php');
require(dirname(dirname(__FILE__)).'/locallib.php');

$res = array();

/*
 * expects input :
 * [
 *    { shortname => "", fullname => "",
 *        category => 1, visible => 1, idnumber => "",
 *        startdate => '', isa => "NEW" }
 *    ...
 * ]
 *
 * :shortname => module_code,
 * :fullname => module_title,
 * :category => (category_id.nil? ? 1 : category_id),
 * :summary => synopsis_for_moodle,
 * :visible => 1,
 * :idnumber => chksum,
 * :moodle_id => moodle_id,
 * :module_length => module_length,
 * :module_version => module_version,
 * :module_week_beginning => module_week_beginning,
 * :module_code => module_code,
 * :module_title => module_title,
 * :synopsis => synopsis,
 * :campus_desc => campus_desc,
 * :startdate => week_beginning_date.to_i,
 * :session_code => session_code,
 * :module_delivery_key => module_delivery_key,
 * :isa => state?(:disengage) ? 'DELETE' : (moodle_id.nil? ? 'NEW' : 'UPDATE')
 *
 * sends output :
 * [
 *     { 'result' => 'ok' or 'error', 'exception' => if error
 *         moodle_course_id => 123, 'in' => input }
 * ]
 */

foreach (json_decode(file_get_contents('php://stdin')) as $c) {
    $tr = array();
    try {
        if (empty($c->idnumber)) {
            throw new moodle_exception('empty idnumber');
        }

        if (empty($c->module_delivery_key)) {
            throw new moodle_exception('Incompatible Connect Version Detected');
        }

        // force 2012/2013 on shortnames and titles for everything
        $prev_year = date('Y', strtotime('1-1-' . $c->session_code . ' -1 year'));
        if (preg_match('/\(\d+\/\d+\)/is', $c->shortname) === 0) {
            $c->shortname .= " ($prev_year/$c->session_code)";
        }

        if (preg_match('/\(\d+\/\d+\)/is', $c->fullname) === 0) {
            $c->fullname .= " ($prev_year/$c->session_code)";
        }

        // Always invisible by default.
        $c->visible = 0;

        // Do we have a record in the Moodle data clone?
        $mdl_connect_course = $DB->get_record('connect_course', array(
            "module_delivery_key" => $c->module_delivery_key,
            "session_code" => $c->session_code,
            "module_version" => $c->module_version
        ));

        // Grab the ID of our new Connect's version of the course.
        $id = ($r === false) ? $DB->insert_record('connect_course', $c) : $mdl_connect_course->id;

        if ($c->isa == 'NEW') {
            $course = \local_connect\course::get($id);
            if (!$course->is_in_moodle()) {
                $course->create_in_moodle();
            }

            $tr = array( 'result' => 'ok', 'moodle_course_id' => $course->mid, 'in' => $c );
        } else if ($c->isa == 'UPDATE') {
            $course->synopsis = $c->synopsis;
            $course->category = $c->category;
            $course->module_week_beginning = $c->module_week_beginning;
            $course->week_beginning_date = $c->startdate;
            $course->module_title = $c->module_title;
            $course->module_code = $c->module_code;

            $course->save();
            $course->update_moodle();

            $tr = array( 'result' => 'ok', 'moodle_course_id' => $c->moodle_id, 'unlocked' => $connect_data->unlocked, 'in' => $c );
        } else if ($c->isa == 'DELETE') {
            $course->delete();

            $tr = array( 'result' => 'ok', 'in' => $c );
        } else {
            throw new moodle_exception('dont understand '.$c->isa);
        }
    } catch (Exception $e) {
        $tr = array(
            'result' => 'error',
            'in' => $c,
            'exception' => $e->getMessage()
        );
    }

    $res [] = $tr;
}

echo json_encode($res);
