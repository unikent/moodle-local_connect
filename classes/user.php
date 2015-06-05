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

namespace local_connect;

defined('MOODLE_INTERNAL') || die();

/**
 * Connect user container
 */
class user extends data
{
    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return "connect_user";
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array("id", "mid", "ukc", "login", "title", "initials", "family_name");
    }

    /**
     * A list of immutable fields for this data object.
     */
    protected static function immutable_fields() {
        return array("id", "ukc");
    }

    /**
     * A list of key fields for this data object.
     */
    protected static function key_fields() {
        return array("id");
    }

    /**
     * Returns all enrolments for this user.
     */
    public function _get_enrolments() {
        return enrolment::get_by("userid", $this->id, true);
    }

    /**
     * Returns all group enrolments for this user.
     */
    public function _get_group_enrolments() {
        return group_enrolment::get_by("userid", $this->id, true);
    }

    /**
     * Returns the Moodle URL for this user.
     */
    public function get_moodle_url() {
        if (empty($this->mid)) {
            return "";
        }

        $url = new \moodle_url("/user/view.php", array("id" => $this->mid));
        return $url->out(false);
    }

    /**
     * Is this user in Moodle?
     * @return boolean [description]
     */
    public function is_in_moodle() {
        return !empty($this->mid);
    }

    /**
     * Sync all my enrolments
     */
    public function sync_enrolments() {
        if (empty($this->mid)) {
            return false;
        }

        $enrolments = array_merge($this->enrolments, $this->group_enrolments);
        foreach ($enrolments as $enrolment) {
            if (!$enrolment->is_in_moodle()) {
                $enrolment->create_in_moodle();
            }
        }
    }

    /**
     * Create this user in Moodle.
     */
    public function create_in_moodle() {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/user/lib.php");

        if ($this->is_in_moodle()) {
            return true;
        }

        if (empty($this->login)) {
            return false;
        }

        // Try to link up if there is already a matching user.
        if ($obj = $DB->get_record('user', array('username' => $this->login))) {
            $this->mid = $obj->id;
            if ($this->save()) {
                $this->sync_enrolments();
            }

            return true;
        }

        if (empty($this->initials) || empty($this->family_name)) {
            return false;
        }

        $user = self::get_user_object($this->login, $this->initials, $this->family_name);

        try {
            $this->mid = user_create_user($user, false);
        } catch (\moodle_exception $e) {
            // TODO - error.
            return false;
        }

        if ($this->save()) {
            $this->sync_enrolments();
        }

        return true;
    }

    /**
     * Given a (username, firstname and lastname) create a user object.
     */
    public static function get_user_object($username, $firstname, $lastname) {
        global $CFG;

        $user = new \stdClass();
        $user->username = \core_text::convert($username, 'utf-8', 'utf-8');
        $user->email = $user->username . "@kent.ac.uk";
        $user->auth = "kentsaml";
        $user->password = "not cached";
        $user->confirmed = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->firstname = $firstname;
        $user->lastname = $lastname;

        return $user;
    }

    /**
     * Delete this user from Moodle
     */
    public function delete() {
        $user = new \stdClass();
        $user->id = $this->mid;
        $user->username = $this->login;
        delete_user($user);

        $this->mid = null;
        $this->save();
    }

    /**
     * Returns a list of all known users in a given role.
     */
    public static function get_by_role($role) {
        global $DB;

        // Allow a special "staff" case that covers convenors and teachers.
        $selector = '=';
        if ($role === 'staff') {
            $selector = '<>';
            $role = 'sds_student';
        }

        $roleid = $DB->get_field('connect_role', 'id', array(
            'name' => $role
        ));

        $sql = "SELECT cu.*
            FROM {connect_user} cu
            INNER JOIN {connect_enrolments} ce ON ce.userid=cu.id
            WHERE ce.roleid $selector :roleid";
        $data = $DB->get_records_sql($sql, array(
            "roleid" => $roleid
        ));

        $result = array();
        foreach ($data as $obj) {
            if (isset($result[$obj->login]) || empty($obj->login)) {
                continue;
            }

            $user = new static();
            $user->set_data($obj);
            $result[$obj->login] = $user;
        }

        return $result;
    }

    /**
     * Returns a list of all known students.
     */
    public static function get_students() {
        return static::get_by_role('sds_student');
    }

    /**
     * Returns a list of all known students.
     */
    public static function get_staff() {
        return static::get_by_role('staff');
    }
}