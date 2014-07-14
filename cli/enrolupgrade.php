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

define('CLI_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');

echo "Upgrading {$CFG->kent->distribution}...\n";

$roleids = $DB->get_fieldset_sql("SELECT mid FROM {connect_role} WHERE mid > 0");
if (!$roleids) {
    die("Nothing to do! \n");
}

list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'roleid');

$enrol = enrol_get_plugin('manual');

\local_connect\course::batch_all(function($course) use ($rolesql, $roleparams, $enrol) {
    global $DB;

    if ($course->is_in_moodle()) {
        echo "    Upgrading Course {$course->mid}...\n";

        $course->sync_enrolments();

        $ctx = \context_course::instance($course->mid);

        $instance = $DB->get_record('enrol', array(
            'enrol' => 'manual',
            'courseid' => $ctx->instanceid,
            'status' => ENROL_INSTANCE_ENABLED
        ));

        if (!$instance) {
                   return;
        }

        $roleparams['contextid'] = $ctx->id;

        $users = $DB->get_fieldset_sql("SELECT ra.userid
            FROM {role_assignments} ra
            WHERE ra.contextid=:contextid AND (ra.roleid $rolesql)
            GROUP BY ra.userid
        ", $roleparams);

        foreach ($users as $userid) {
            $enrol->unenrol_user($instance, $userid);
        }
    }
});