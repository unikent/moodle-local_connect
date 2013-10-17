<?php 

	require_once('../../config.php');

	global $CFG;
	
	$scripts = array(
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

	$includes .= '<script src="' . $CFG->wwwroot . '/lib/less/less-1.4.2.min.js" type="text/javascript"></script>';
	$includes .= '<script src="' . $CFG->wwwroot . '/lib/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>';
	foreach($scripts as $script) {
		$includes .= '<script src="' . $CFG->wwwroot . '/local/connect/scripts/' . $script . '" type="text/javascript"></script>';
	}

	echo $includes;
