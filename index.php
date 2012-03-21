<?php

require_once('../../config.php');

global $USER, $PAGE;

require_login();

$site_context = context_system::instance();
$PAGE->set_context($site_context);

if (!has_capability('local/kent-connect:manage', $site_context)) {
    print_error('accessdenied', 'local_connect');
}

$PAGE->set_url('/local/connect/index.php');
$PAGE->set_pagelayout('datool');

echo $OUTPUT->header();

$clareport_text = get_string('connectreport', 'local_connect');
echo $OUTPUT->heading($clareport_text);

$scripts = '<link rel="stylesheet" type="text/css" href="styles/demo_table.css">';
$scripts .= '<link rel="stylesheet/less" type"text/css" href="styles/styles.less">';
$scripts .='<script src="' . $CFG->wwwroot . '/lib/less/less-1.2.0.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/lib/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/rollover/scripts/js/jquery-ui-1.8.17.custom.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/connect/scripts/jquery.dataTables.min.js" type="text/javascript"></script>';
$scripts .='<script src="' . $CFG->wwwroot . '/local/connect/scripts/jquery.dataTables.columnFilter.js" type="text/javascript"></script>';
echo $scripts;

$table = <<< HEREDOC
	<div id= "dapage_app">
		<div id="options_bar">
			<ul id="status_toggle">
				<li><input type="checkbox" name="new" value="1"  id="new" class="status_checkbox"/><label for="new">new</label></li>
				<li><input type="checkbox" name="pending" value="2"  id="pending" class="status_checkbox"/><label for="pending">pending</label></li>
				<li><input type="checkbox" name="active" value="3"  id="active" class="status_checkbox"/><label for="active">active</label></li>
				<li><input type="checkbox" name="error" value="4"  id="error" class="status_checkbox"/><label for="error">error</label></li>
			</ul>
			<div id="dasearch">
				<input type="text" />
			</div>
		</div>
		<table id="datable">
			<thead>
				<tr>
					<th>Status</th>
					<th >Code</th>
					<th>Name</th>
					<th>Campus</th>
					<th>Duration</th>
				</tr>
			</thead>
			<tfoot>
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
HEREDOC;

echo $table;

$scripts = '<script src="' . $CFG->wwwroot . '/local/connect/scripts/app.js" type="text/javascript"></script>';

echo $scripts;

echo $OUTPUT->footer();