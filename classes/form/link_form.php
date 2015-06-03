<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local stuff for Moodle Connect
 *
 * @package    local_connect
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class link_form extends \moodleform
{
    /**
     * Form definition
     */
    public function definition() {
        global $PAGE;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'mid', $PAGE->course->id);
        $mform->setType('mid', PARAM_INT);

        $mform->addElement('text', 'module_delivery_key', 'Module Delivery Key');
        $mform->setType('module_delivery_key', PARAM_TEXT);
        $mform->addHelpButton('module_delivery_key', 'module_delivery_key', 'local_connect');
        $mform->addRule('module_delivery_key', null, 'required', null, 'client');

        $this->add_action_buttons(true);
    }

    /**
     * Set default.
     */
    public function set_field_default($field, $val = 0) {
        $mform =& $this->_form;
        $mform->setDefault($field, $val);
    }
}
