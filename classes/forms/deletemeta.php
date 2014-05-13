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
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_connect\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class deletemeta extends \moodleform
{
    /** The ID we are referencing. */
    private $id;

    /**
     * Constructor
     */
    public function __construct($id, $action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true) {
        $this->id = $id;
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable);
    }

    /**
     * Form definition
     */
    public function definition() {
        $mform =& $this->_form;

        // Add rule prefix.
        $mform->addElement('hidden', 'id', $this->id);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, "Delete");
    }
}
