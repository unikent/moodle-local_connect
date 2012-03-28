<?php

require_once('../../config.php');

global $USER, $PAGE, $CFG;

require_login();

$site_context = context_system::instance();
$PAGE->set_context($site_context);

if (!has_capability('local/kentconnect:manage', $site_context)) {
    print_error('accessdenied', 'local_connect');
}

$PAGE->set_url('/local/connect/index.php');
$PAGE->set_pagelayout('datool');

echo $OUTPUT->header();

$clareport_text = get_string('connectreport', 'local_connect');
echo $OUTPUT->heading($clareport_text);

$scripts = '<link rel="stylesheet" type="text/css" href="styles/demo_table.css">';
$scripts = '<link rel="stylesheet" type="text/css" href="scripts/css/ui-lightness/jquery-ui-1.8.17.custom.css">';
$scripts .= '<link rel="stylesheet/less" type"text/css" href="styles/styles.less">';

$scripts .='<script src="' . $CFG->wwwroot . '/lib/less/less-1.2.0.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/lib/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/connect/scripts/jquery-ui-1.8.18.custom.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/connect/scripts/jquery.dataTables.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/connect/scripts/jquery.dataTables.columnFilter.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/connect/scripts/naturalSort.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/connect/scripts/underscore-min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/connect/scripts/js/jquery-ui-1.8.17.custom.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/connect/scripts/js/jquery.blockUI.js" type="text/javascript"></script>';
echo $scripts;

$table = <<< HEREDOC
<div id="da_wrapper">
	<div id= "dapage_app">
		<div id="options_bar">
			<ul id="status_toggle">
				<li><input type="checkbox" name="unprocessed" value="unprocessed" id="unprocessed" class="status_checkbox" checked="checked"><label id="label-unprocessed" for="unprocessed">unprocessed</label></li>
				<li><input type="checkbox" name="processing" value="processing" id="processing" class="status_checkbox"><label id="label-processing" for="processing">processing</label></li>
				<li><input type="checkbox" name="scheduled" value="scheduled" id="scheduled" class="status_checkbox"><label id="label-scheduled" for="scheduled">scheduled</label></li>
				<li><input type="checkbox" name="created_in_moodle" value="created_in_moodle" id="created_in_moodle" class="status_checkbox"><label id="label-created_in_moodle" for="created_in_moodle">created in moodle</label></li>
				<li><input type="checkbox" name="failed_in_moodle" value="failed_in_moodle" id="failed_in_moodle" class="status_checkbox"><label id="label-failed_in_moodle" for="failed_in_moodle">failed in moodle</label></li>
			</ul>
			<div id="dasearch">
				<input type="text" id="dasearch-box" name="dasearch-box" />
			</div>
		</div>
		<table id="datable">
			<thead>
				<tr>
					<th>Id</th>
					<th>Status</th>
					<th >Code</th>
					<th>Name</th>
					<th>Campus</th>
					<th>Duration</th>
				</tr>
			</thead>
			<tfoot>
					<th></th>
					<th id="filter-status"></th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
			</tfoot>
			<tbody>
			</tbody>
		</table>
	</div>

	<div id="jobs_wrapper">
		<div id="jobs">
			<div class="job_number_text">you currently have</div>
			<div id="job_number">0</div>
			<div class="job_number_text">deliveries selected</div>
			<div id="display_list_toggle">
				<button>show deliveries</button>
				<div class="arrow_border"></div>
				<div class="arrow_light"></div>
			</div>
			<ul>
			</ul>
		</div>
		<div id="process_jobs">
			<button id="push_deliveries" disabled="disabled">No selection</button>
		</div>
	</div>
</div>
	<script type="text/javascript">
		window.dapageUrl = '$CFG->daPageUrl';
	</script>
HEREDOC;

echo $table;

echo '<div id="dialog_error">'.get_string('connect_error', 'local_connect').'</div>';

$scripts = '<script src="' . $CFG->wwwroot . '/local/connect/scripts/app.js" type="text/javascript"></script>';

echo $scripts;

echo $OUTPUT->footer();