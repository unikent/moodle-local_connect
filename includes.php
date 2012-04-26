<?php 

	require_once('../../config.php');

	global $CFG;
	
	$scripts = array(
		'less-1.2.0.min.js',
		'jquery-1.7.1.min.js',
		'jquery-ui-1.8.18.custom.min.js',
		'jquery.blockUI.js',
		'jquery.dataTables.min.js',
		'jquery.dataTables.columnFilter.js',
		'underscore-min.js',
		'json2.js',
		'date-en-GB.js',
		'connect.js',
		'button-loader.js'
		);

	$includes = '';

	foreach($scripts as $script) {
		$includes .= '<script src="' . $CFG->wwwroot . '/local/connect/scripts/' . $script . '" type="text/javascript"></script>';
	}

	echo $includes;
