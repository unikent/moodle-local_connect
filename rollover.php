<?php
/**
 * Prototype Rollover Page
 */

require(dirname(__FILE__) . '/../../config.php');

global $PAGE, $OUTPUT, $USER, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/rollover.php');
$title = get_string('rollover_heading', 'local_connect');
$PAGE->set_title($title);

if (!\local_connect\utils::enable_new_features()) {
	print_error('new_feature_disabled', 'local_connect');
}

$PAGE->requires->js_init_call('M.local_rollover.init', array(), false, array(
    'name' => 'local_enrolment',
    'fullpath' => '/local/connect/scripts/rollover.js',
    'requires' => array("autocomplete")
));

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

echo '<div class="warning box">Warning! These pages are currently in BETA and may not be stable.</div>';

echo $OUTPUT->box_start();
echo '<form action="rollover.php" method="post" id="rolloversettings">';
echo '<div class="settingsform clearfix">';
echo html_writer::input_hidden_params($PAGE->url);
echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

echo '<div class="c2 fld sourceid"><label for="rollover_source">Rollover source: </label>';
echo '<select name="rollover_source" id="rollover_source">';
echo '<option value="release">Moodle RD</option>';
echo '<option value="2013">Moodle 2013</option>';
echo '<option value="2012">Moodle 2012</option>';
echo '<option value="archive">Moodle Archive</option>';
echo '</select>';
echo '</div>';

echo '<div class="c1 fld targetid"><label for="rollover_target">Rollover into: </label>';
echo '<input type="text" name="target_id" id="rollover_target" /></div>';

echo '<div class="c2 fld sourceid"><label for="source_id">Rollover from: </label>';
echo '<input type="text" name="source_id" id="source_id" /></div>';

echo '<div class="form-buttons"><input class="form-submit" type="submit" value="Rollover" /></div>';

echo '</div>';
echo '</form>';
echo $OUTPUT->box_end();

echo $OUTPUT->footer();