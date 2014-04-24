<?php
		//Required elements
	define('CLI_SCRIPT', 1);
	require_once(dirname(__FILE__).'/../../../config.php');
	require_once($CFG->dirroot.'/backup/util/helper/convert_helper.class.php');
	require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

	error_reporting(E_ALL);
	
		//Parse arguments from standard in
		$import_settings = kent_stdin_to_array();

		try {

			//Start db transaction
			$transaction = $DB->start_delegated_transaction();

			$name = uniqid( 'rollover-' );
			
			if (!file_exists($CFG->tempdir . '/backup')) {
				if (!mkdir($CFG->tempdir . '/backup')) {
					throw new Exception("Could not create backup directory [" . $CFG->tempdir . '/backup] ... check permissions?');
				}
			}

			// this line prevents 1.9 restores from working as the backup folder for 1.9 is in
			// a different directory -- also, the 1.9 backup script outputs the full backup
			// folder as an output.
			//
			// when we support 2.0 -> 2.0 rollovers, we should probably also make sure the 2.0
			// backup script outputs the full backup path too, so we don't need to change the
			// new renaming line below this one
			//if(!rename( $CFG->tempdir . '/backup/' . $import_settings['folder'], $CFG->tempdir . '/backup/' . $name) ) {
      $from = $import_settings['folder'];
      $to = $CFG->tempdir . '/backup/' . $name;
      exec( "mv $from $to", $_, $return );
      if( $return != 0 ) {
        throw new Exception( "could not rename [" . $import_settings['folder'] . "] -> [" . $CFG->tempdir . '/backup/' . $name . ']');
      }

			//Checks if the the moodle backup needs converting to 2.*
			if(convert_helper::detect_moodle2_format($name)){
			   
			   		// remove any references to turnitin
					kent_manipulate_backup_v2($CFG->tempdir . '/backup/' . $name . '/moodle_backup.xml');

					// Restore backup into course with arguments of foldername and course id
					kent_restore($name, $import_settings['id']);
					
			} else {
				
				// before we do anything, we need to manipulate the xml to remove any turnitin
				// module instances (or others)
				kent_manipulate_backup($CFG->tempdir . '/backup/' . $name . '/moodle.xml');

				//This function does what it says and the arguments are the foldername and the backupformat
				convert_helper::to_moodle2_format($name, 'moodle1');
				
				// Restore backup into course with arguments of foldername and course id
					kent_restore($name, $import_settings['id']);

			}
			
			// Commit
			$transaction->allow_commit();
			exit(0);
			
		} catch (Exception $e) {
				//Roll back the database transaction catching any exceptions.
				try{
					$DB->rollback_delegated_transaction($transaction, $e);
				} catch (Exception $e) {
					 kent_error_log("ERROR: ".$e->getMessage());
				}
				exit(1);
		}
		
/* Funtion that pull in preferences from standard in
 * @return array for successfuly extraction of prefs, or FALSE for invalid return
 */
		
function kent_stdin_to_array(){
	
	$key_val_string_array = file('php://stdin');

	if($key_val_string_array != FALSE){
		//Generate our pref array from the file
		//Expects format:  key=value\n
		$prefs = array();
		foreach($key_val_string_array as $key_val_string){
				if (($temp_pref = explode("=", $key_val_string)) && count($temp_pref) == 2 && trim($temp_pref[0]) != ""){
						$prefs[trim($temp_pref[0])] = trim($temp_pref[1]);
				}
		}

		//No prefs check
		if (empty($prefs)){
			kent_error_log("ERROR: Could not get Moodle 2.0 backup settings for course backup request.");  
		}

		return $prefs;
	} else {
		kent_error_log("ERROR: Could not get Moodle 2.0 backup settings for course backup request via fopen.");
	}
}

/* Wrapper function for the backup functions
 * @param string $folder name of folder backup is in
 * @param int $id id of course that backup will be restored to
 */

function kent_restore($folder, $id){
	$controller = new restore_controller($folder, $id,
	backup::INTERACTIVE_NO, backup::MODE_GENERAL, 2,
	backup::TARGET_EXISTING_ADDING);
	if ($controller->execute_precheck()) {
		$controller->execute_plan();
	} else {
		kent_error_log("Plan pre-check failed. " . var_dump($controller->get_precheck_results()));
	}
}

/* Quick wrapper function for error_log, adds in echo output to CLI if specified at the top.
 * @param string $msg error message
 * @param int $level optional level of error message
 */
function kent_error_log($msg, $level=0){
	global $debug, $stream_output;
	if($debug && $stream_output){
		echo $msg;
	}
	error_log($msg, $level);
	exit(1);//Exit error code
}

/**
 * Manipulate the backup xml source to remove deprecated things, like turnitin
 * 
 * This function will operate directly on the main moodle.xml file in the backup,
 * removing references to any turnitin assignments (though it could be extended
 * to remove other stuff that we don't support). It's easier to do this here than
 * to hack the backup/restore functions in Moodle to ignore these modules.
 *
 * @param $backup_file The moodle.xml file containing the backup data
 */
function kent_manipulate_backup($backup_file) {

	$doc = new DOMDocument();
	if (!$doc->load($backup_file)) {
		throw new Exception('[kent_manipulate_backup] Could not load backup file <' . $backup_file . '>');
	}

	$xpath = new DOMXPath($doc);

	// get all the instance nodes of assignment type turnitin
	$query = "/MOODLE_BACKUP/COURSE/MODULES/MOD[ASSIGNMENTTYPE='turnitin']";
	$turnitin_instance_nodes = $xpath->query($query);

	if ($turnitin_instance_nodes->length == 0) {
		return true; // nothing to do, no turnitins assignments found
	}

	// loop through each turnitin instance to remove any references to it
	// from the rest of the document
	foreach ($turnitin_instance_nodes as $main_node) {

		// this should get us the instance ID for this turnitin module
		try {
			$instance_id = $main_node->getElementsByTagName('ID')->item(0)->textContent;
		} catch (Exception $e) {
			throw new Exception("[kent_manipulate_backup] Could not get ID for turnitin 
				module instance (backup XML format is probably broken or has changed)");
		}
		
		// using this instance id, we can now find and remove elements which
		// reference it, fun fun fun!

		// find & remove from INFO/DETAILS
		$detail_instances = $xpath->query(
			"/MOODLE_BACKUP/INFO/DETAILS/MOD/INSTANCES/INSTANCE[ID='${instance_id}']"
		);

		foreach ($detail_instances as $instance_node) {
			$instance_node->parentNode->removeChild($instance_node);
		}

		// find & remove from SECTIONS
		$section_instances = $xpath->query(
			"/MOODLE_BACKUP/COURSE/SECTIONS/SECTION/MODS/MOD[INSTANCE='${instance_id}']"
		);

		foreach ($section_instances as $instance_node) {
			$instance_node->parentNode->removeChild($instance_node);
		}

		// find & remove from GRADEBOOK
		$grade_instances = $xpath->query(
			"/MOODLE_BACKUP/COURSE/GRADEBOOK/GRADE_ITEMS/GRADE_ITEM[ITEMINSTANCE='${instance_id}']"
		);

		// I don't think the grade items are referenced anywhere else, but if they are,
		// this will probably break as they would need to be dereferenced properly

		foreach ($grade_instances as $instance_node) {
			$instance_node->parentNode->removeChild($instance_node);
		}

		// finally, remove the actual base instance from COURSE/MODULES/MOD
		$main_node->parentNode->removeChild($main_node);
	}

	if ($doc->save($backup_file) === false) {
		throw new Exception('[kent_manipulate_backup] Could not 
			overwrite backup file <' . $backup_file . '>');
	}

	return true;

}

/**
 * Manipulate the backup xml source to remove deprecated things, like turnitin
 * 
 * This function will operate directly on the main moodle.xml file in the backup,
 * removing references to any turnitin assignments (though it could be extended
 * to remove other stuff that we don't support). It's easier to do this here than
 * to hack the backup/restore functions in Moodle to ignore these modules.
 *
 * @param $backup_file The moodle.xml file containing the backup data
 */
function kent_manipulate_backup_v2($backup_file) {

	$doc = new DOMDocument();
	if (!$doc->load($backup_file)) {
		throw new Exception('[kent_manipulate_backup] Could not load backup file <' . $backup_file . '>');
	}

	$xpath = new DOMXPath($doc);

	// get all turnitintool activities
	$query = "/moodle_backup/information/contents/activities/activity[modulename/text()='turnitintool']";
	$turnitintool_activity_nodes = $xpath->query($query);

	// remove them
	foreach($turnitintool_activity_nodes as $activity_node) {
		$activity_node->parentNode->removeChild($activity_node);
	}

	// get all turnitintool settings
	$query = "/moodle_backup/information/settings/setting[activity/text()[contains(.,'turnitintool')]]";
	$turnitintool_setting_nodes = $xpath->query($query);

	// remove them
	foreach($turnitintool_setting_nodes as $setting_node) {
		$setting_node->parentNode->removeChild($setting_node);
	}

	if ($doc->save($backup_file) === false) {
		throw new Exception('[kent_manipulate_backup] Could not 
			overwrite backup file <' . $backup_file . '>');
	}

	return true;

}