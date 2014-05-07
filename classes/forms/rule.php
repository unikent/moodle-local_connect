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

class rule extends \moodleform
{
    /**
     * Form definition
     */
    public function definition() {
        global $DB;

        $mform =& $this->_form;
        $strrequired = get_string('required');

        // Add rule prefix.
        $mform->addElement('text', 'prefix', "Course Prefix");
        $mform->addRule('prefix', $strrequired, 'required', null, 'client');
        $mform->setType('prefix', PARAM_ALPHA);

        // Add category dropdown.
        $categories = $DB->get_records('course_categories', null, 'name ASC', 'id, name');
        $options = array();
        foreach ($categories as $category) {
            $options[$category->id] = $category->name;
        }
        $mform->addElement('select', 'category', "Category", $options);

        $this->add_action_buttons(true, "Create Rule");
    }
}
