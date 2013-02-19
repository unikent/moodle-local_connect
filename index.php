<?php

require_once('../../config.php');

global $USER, $PAGE, $CFG;

require_login();

$site_context = context_system::instance();
$PAGE->set_context($site_context);

$PAGE->set_url('/local/connect/index.php');
$PAGE->set_pagelayout('datool');

echo $OUTPUT->header();
$theCats='';
$cats = $DB->get_records('course_categories');
$cat_permissions = array();

foreach($cats as $cat) {
	$context = get_context_instance(CONTEXT_COURSECAT, $cat->id);

	if(has_capability('moodle/category:manage', $context)) {
		array_push($cat_permissions, $cat->id);
		$theCats .= '<option value="'.$cat->id.'">'.$cat->name.'</option>';
	}
}

if(count($cat_permissions) == 0) {
	print_error('accessdenied', 'local_connect');
}

if (has_capability('local/kentconnect:manage', $site_context)) {
	$cat_json = json_encode("");
} else {
	$cat_json = json_encode($cat_permissions);
}

$scripts = '<link rel="stylesheet" type="text/css" href="styles/demo_table.css">';
$scripts = '<link rel="stylesheet" type="text/css" href="scripts/css/ui-lightness/jquery-ui-1.8.17.custom.css">';
$scripts .= '<link rel="stylesheet/less" type"text/css" href="styles/styles.less">';
echo $scripts;

require_once('includes.php');

$clareport_text = get_string('connectreport', 'local_connect');
echo $OUTPUT->heading($clareport_text);

echo '<div id="key_button" class="show_key"><div id="key_button_wrap">Show key</div></div>';

echo <<< HEREDOC
<div id="key">
	<div id="key_wrap">
		<ul>
			<li class="status_key key_item">= Status indicator (normaly coloured)</li>
			<li class="warning_key key_item">= Delivery is no longer in sds</li>
			<li class="link_key key_item">= Link to active moodle delivery</li>
			<li class="delete_key key_item">= Removed delivery from moodle</li>
			<li class="unlink_key key_item">= Unlink child delivery from parent</li>
			<li class="flag_key key_item">= Delivery shares its module code with an already created module</li>
		</ul>
	</div>
</div>
<div id="key_margin"></div>
HEREDOC;

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
					<th>Students</th>
					<th>Version</th>
					<th></th>
				</tr>
			</thead>
			<tfoot>
					<th></th>
					<th id="filter-status"></th>
					<th></th>
					<th></th>
					<th></th>
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
		<div id="select_buttons">
			<div class="sel_btn" id="select_all"> Select all</div>
			<div class="sel_btn" id="deselect_all"> Deselect all</div>
		</div>
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
			<button id="merge_deliveries" disabled="disabled">No selection</button>
		</div>
	</div>
</div>
	<script type="text/javascript">
		window.dapageUrl = '$CFG->daPageUrl';
		window.coursepageUrl = '$CFG->wwwroot';
		window.cats = $cat_json;
	</script>
<div id="dialog-form" title="Edit details">
	<div id="edit_notifications"></div>
	<form>
	<fieldset>
		<table>
			<tr>
				<td><label for="shortname">Shortname</label></td>
				<td><input type="text" disabled="disabled" name="shortname" id="shortname" class="text ui-widget-content ui-corner-all" /></td>
				<td id="shortname_ext_td"></td>
			</tr>
			<tr>
				<td><label for="fullname">Fullname</label></td>
				<td colspan="2"><input type="text" name="fullname" id="fullname" value="" class="text ui-widget-content ui-corner-all"/></td>
			</tr>
			<tr>
				<td><label for="synopsis">Synopsis</label></td>
				<td colspan="2"><textarea maxlength="500" name="synopsis" id="synopsis" class="text ui-widget-content ui-corner-all"></textarea></td>
			</tr>
			<tr>
				<td><label for="category">Category</label></td>
				<td colspan="2"><select name="category" id="category">$theCats</select></td>
			</tr>
		</table>
	</fieldset>
	</form>
</div>
<div id="dialog-confirm" title="Confirm">
	<p>
		<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>
		These deliveries will be pushed as-is. If you want to edit a module's information, please push it separately.
	</p>
</div>

HEREDOC;

echo $table;

echo '<div id="dialog_error">'.get_string('connect_error', 'local_connect').'</div>';

$scripts = '<script src="' . $CFG->wwwroot . '/local/connect/scripts/app.js" type="text/javascript"></script>';

echo $scripts;

echo $OUTPUT->footer();
