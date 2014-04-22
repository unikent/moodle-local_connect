<?php
//Required elements
define('CLI_SCRIPT', 1);
require_once(dirname(__FILE__)."/../../config.php");
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

class kent_backup_plan extends backup_plan implements loggable {
    
    public function kent_edit_settings($prefs) {
        $users = $this->get_setting('users');
        $users->set_value(0);
        $pattern = "/(?:[a-z][a-z]+)(_)(\d+)(_)(?:[a-z][a-z]+)/";
        
        //Loop through our settings array
        foreach($prefs as $pref=>$value) {
            //Check that the chosen pref is not already dealt with
            if($pref != 'id' || $pref != 'users'){
                //Loops through backup settings
                foreach($this->settings as $key => $setting) {
                    //Checks to see if it is a resource or global course setting
                    if (preg_match($pattern, $setting->get_name())) {
                        $s = explode('_', $setting->get_name());
                        //Checks to see if our pref and the backup setting match
                        if ($s[0] == $pref) {
                            //See if it is a userinfo setting
                            if ($s[2] == 'userinfo' && $users->get_value() == 1 && $value == 1) {
                                $setting->set_value(1);
                            } else if ($s[2] == 'included') {
                               $setting->set_value((int)$value); 
                            } else {
                                $setting->set_value(0);
                            }   
                        }  
                    } else {
                        if($setting->get_name() == $pref) {
                            $setting->set_value((int)$value);
                        }
                    }
                    
                }
            }
        }
    }
}

class kent_backup_controller extends backup_controller implements loggable {
    public function __construct($type, $id, $format, $interactive, $mode, $userid, $prefs) {
        parent::__construct($type, $id, $format, $interactive, $mode, $userid);
        $this->prefs = $prefs;
    }

    protected function load_plan() {
        $this->log('loading controller plan', backup::LOG_DEBUG);
        $this->plan = new kent_backup_plan($this);
        $this->plan->build(); // Build plan for this controller
        $this->set_status(backup::STATUS_PLANNED);
    }

    protected function apply_defaults() {
        parent::apply_defaults();
        $this->plan->kent_edit_settings($this->prefs);
    }
}


$import_settings = kent_stdin_to_array();

//Start backup script

$bc = new kent_backup_controller(backup::TYPE_1COURSE, $import_settings['id'], backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_GENERAL, 2, $import_settings);
$bc->execute_plan();

$filedets = $bc->get_results();

if ($filedets['backup_destination']->get_contenthash()) {
    $packer = get_file_packer('application/vnd.moodle.backup');
    $filedets['backup_destination']->extract_to_pathname($packer, $CFG->tempdir . '/backup/' . $filedets['backup_destination']->get_contenthash());
    $filedets['backup_destination']->delete();
    echo $CFG->tempdir . '/backup/' . $filedets['backup_destination']->get_contenthash();
    exit(0); 
} else {
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

