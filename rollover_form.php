<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/lib/formslib.php');

class connect_rollover_form extends moodleform {

    // Define the form
    function definition() {
        global $USER, $CFG, $COURSE;

        $mform =& $this->_form;
        $strrequired = get_string('required');

        // Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'moodle', get_string('rollover_form_heading', 'local_connect'));

        // Add selection dropdown
		$options = array();
		foreach ($CFG->connect->rollover_sources as $source) {
			$options[$source] = "Moodle $source";
		}
        $mform->addElement('select', 'source_moodle', get_string('rollover_form_source', 'local_connect'), $options);

        // Add box to select course to roll into
        $mform->addElement('text', 'target', get_string('rollover_form_to', 'local_connect'));
        $mform->addRule('target', $strrequired, 'required', null, 'client');
        $mform->setType('target', PARAM_INT);

        // Add box to select course to rollover from
        $mform->addElement('text', 'source', get_string('rollover_form_from', 'local_connect'));
        $mform->addRule('source', $strrequired, 'required', null, 'client');
        $mform->setType('source', PARAM_INT);

        $this->add_action_buttons(true, get_string('rollover', 'local_connect'));
    }

    function definition_after_data() {
    }

    function validation($usernew, $files) {
        return true;
    }
}


