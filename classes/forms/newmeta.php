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

class newmeta extends \moodleform
{
    /** The stage we are at. */
    private $stage;

    /**
     * Constructor
     */
    public function __construct($stage, $action=null, $customdata=null, $method='post', $target='',
                                $attributes=null, $editable=true) {
        $this->stage = $stage;
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable);
    }

    /**
     * Form definition
     */
    public function definition() {
        $this->add_stage();

        $method = "stage{$this->stage}";
        $this->$method();

        $this->add_action_buttons(true, $this->stage == '2' ? 'Create' : 'Next');
    }

    /**
     * Stage 1.
     */
    private function stage1() {
        $mform =& $this->_form;

        $category = \local_connect\meta::OBJECT_TYPE_CATEGORY;
        $course = \local_connect\meta::OBJECT_TYPE_COURSE;
        $group = \local_connect\meta::OBJECT_TYPE_GROUP;
        $role = \local_connect\meta::OBJECT_TYPE_ROLE;

        // Add type dropdown.
        $mform->addElement('select', 'objecttype', "Type", array(
            $category => "Category",
            $course => "Course",
            $group => "Group",
            $role => "Role",
        ));
        $mform->setType('objecttype', PARAM_INT);
    }

    /**
     * Stage 2.
     */
    private function stage2() {
        global $DB;

        $mform =& $this->_form;

        $objecttype = required_param('objecttype', PARAM_INT);

        $mform->addElement('hidden', 'objecttype', $objecttype);
        $mform->setType('objecttype', PARAM_INT);

        // Grab a list of possible objects.
        $objs = array();
        switch ($objecttype) {
            case \local_connect\meta::OBJECT_TYPE_CATEGORY:
                $objs = $DB->get_records_sql('SELECT cc.category as id, cat.name
                    FROM {connect_course} cc
                    INNER JOIN {course_categories} cat ON cat.id=cc.category
                    GROUP BY cc.category');
            break;
            case \local_connect\meta::OBJECT_TYPE_COURSE:
                $objs = $DB->get_records_sql('SELECT id,
                    CONCAT(module_title, " (", module_week_beginning, "-", module_week_beginning+module_length-1, ")") as name
                    FROM {connect_course}');
            break;
            case \local_connect\meta::OBJECT_TYPE_GROUP:
                $objs = $DB->get_records_sql('SELECT cg.id, CONCAT(cc.module_title, " - ", cg.name) as name
                    FROM {connect_group} cg
                    INNER JOIN {connect_course} cc ON cc.id=cg.courseid');
            break;
            case \local_connect\meta::OBJECT_TYPE_ROLE:
                $objs = $DB->get_records('connect_role', null, '', 'id, name');
            break;
        }

        // Sanitise it, as in, ids map to names.
        $options = array();
        foreach ($objs as $obj) {
            $options[$obj->id] = $obj->name;
        }

        // Print the select.
        $mform->addElement('select', 'objectid', "Object", $options);
        $mform->setType('objectid', PARAM_INT);

        // Also, what course is all of this pouring in to?
        $options = array();
        $rs = $DB->get_recordset('course', null, 'fullname', 'id,fullname');
        foreach ($rs as $record) {
            $options[$record->id] = $record->fullname;
        }
        $rs->close();

        // Print the select.
        $mform->addElement('select', 'courseid', "Course", $options);
        $mform->setType('courseid', PARAM_INT);
    }

    /**
     * Stage 3.
     */
    private function stage3() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'objecttype', 0);
        $mform->setType('objecttype', PARAM_INT);
        $mform->addElement('hidden', 'objectid', 0);
        $mform->setType('objectid', PARAM_INT);
        $mform->addElement('hidden', 'courseid', 0);
        $mform->setType('courseid', PARAM_INT);
    }

    /**
     * Add hidden stage element.
     */
    private function add_stage() {
        $mform =& $this->_form;

        $stage = ((int)$this->stage) + 1;

        // Add stage.
        $mform->addElement('hidden', 'stage', $stage);
        $mform->setType('stage', PARAM_INT);
        $mform->setConstant('stage', $stage);
    }
}
