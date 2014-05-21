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

namespace local_connect\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Connect migration scripts
 */
class migrate
{
    /**
     * Run truncates
     */
    public static function empty_all() {
        global $DB;

        echo "Truncating tables...\n";

        $DB->execute('TRUNCATE {connect_campus}');
        $DB->execute('TRUNCATE {connect_course}');
        $DB->execute('TRUNCATE {connect_enrolments}');
        $DB->execute('TRUNCATE {connect_group}');
        $DB->execute('TRUNCATE {connect_group_enrolments}');
        $DB->execute('TRUNCATE {connect_role}');
        $DB->execute('TRUNCATE {connect_user}');
        $DB->execute('TRUNCATE {connect_timetabling}');
        $DB->execute('TRUNCATE {connect_room}');
        $DB->execute('TRUNCATE {connect_type}');
    }

    /**
     * Run all of them.
     */
    public static function all() {
        self::new_rules();
        self::new_roles();
        self::map_roles();
        self::new_users();
        self::map_users();
        self::new_campus();
        self::updated_courses();
        self::new_courses();
        self::updated_groups();
        self::new_groups();
        self::updated_enrolments();
        self::new_enrolments();
        self::updated_group_enrolments();
        self::new_group_enrolments();

        // Timetabling.
        self::new_rooms();
        self::new_timetabling_types();
        self::new_timetabling();
        self::updated_timetabling();
    }

    /**
     * Run all of the creates.
     */
    public static function all_create() {
        self::new_rules();
        self::new_roles();
        self::new_users();
        self::new_campus();
        self::new_courses();
        self::new_groups();
        self::new_enrolments();
        self::new_group_enrolments();
        self::new_rooms();
        self::new_timetabling_types();
        self::new_timetabling();
    }

    /**
     * Run all of the updates.
     */
    public static function all_updated() {
        self::updated_courses();
        self::updated_groups();
        self::updated_enrolments();
        self::updated_group_enrolments();
        self::updated_timetabling();
    }

    /**
     * New Rules
     */
    public static function new_rules() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating new rules\n";

        $sql = "INSERT INTO {connect_rules} (`prefix`, `category`,`weight`) (
            SELECT r.rule, r.mdl_category, 50
            FROM `$connectdb`.`rules` r
            LEFT OUTER JOIN {connect_rules} cr ON cr.prefix=r.rule
            WHERE cr.id IS NULL AND r.rule IS NOT NULL
            GROUP BY r.rule
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * New Roles
     */
    public static function new_roles() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating new roles\n";

        $sql = "INSERT INTO {connect_role} (name) (
            SELECT e.role
            FROM `$connectdb`.`enrollments` e
            LEFT OUTER JOIN {connect_role} cr
                ON cr.name=e.role
            WHERE cr.id IS NULL AND e.session_code=:session_code
            GROUP BY e.role
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * New Users
     */
    public static function new_users() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating new users\n";

        $sql = "INSERT INTO {connect_user} (ukc, login, title, initials, family_name) (
            SELECT e.ukc, e.login, COALESCE(e.title, ''), COALESCE(e.initials, ''), COALESCE(e.family_name, '')
            FROM `$connectdb`.`enrollments` e
            LEFT OUTER JOIN {connect_user} u
                ON u.login=e.login
            WHERE u.id IS NULL AND e.session_code=:session_code
            GROUP BY e.login
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * New Campuses
     */
    public static function new_campus() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating new campus\n";

        $sql = "INSERT INTO {connect_campus} (`id`, `name`) (
            SELECT c.campus, c.campus_desc
            FROM `$connectdb`.`courses` c
            LEFT OUTER JOIN {connect_campus} cc
                ON cc.id=c.campus
            WHERE cc.id IS NULL AND c.session_code=:session_code
            GROUP BY c.campus
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * Updated Courses
     */
    public static function updated_courses() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating updated courses\n";

        $sql = "REPLACE INTO {connect_course} (id,module_delivery_key,session_code,module_version,campusid,module_week_beginning,
        	                                   module_length,week_beginning_date,module_title,module_code,synopsis,category, mid) (
            SELECT cc.id, c.module_delivery_key,c.session_code,COALESCE(c.module_version,1),
                   c.campus as campusid,c.module_week_beginning,c.module_length,c.week_beginning_date,c.module_title,c.module_code,
                   COALESCE(c.synopsis, ''),c.category_id,COALESCE(cc.mid,0)
            FROM `$connectdb`.`courses` c
            INNER JOIN {connect_course} cc ON cc.module_delivery_key=c.module_delivery_key AND cc.session_code=c.session_code
            WHERE (c.module_title <> cc.module_title
                        OR c.module_code <> cc.module_code
                        OR c.synopsis <> cc.synopsis
                        OR c.category_id <> cc.category)
                    AND c.session_code=:session_code
            GROUP BY c.module_delivery_key,c.session_code,c.module_version
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * New Courses
     */
    public static function new_courses() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating new courses\n";

        $sql = "INSERT INTO {connect_course} (module_delivery_key,session_code,module_version,campusid,module_week_beginning,
        	                                   module_length,week_beginning_date,module_title,module_code,synopsis,category, mid) (
            SELECT c.module_delivery_key,c.session_code,COALESCE(c.module_version,1),
                   c.campus as campusid,c.module_week_beginning,c.module_length,c.week_beginning_date,
                   c.module_title,c.module_code,COALESCE(c.synopsis, ''),c.category_id,COALESCE(c.moodle_id, 0)
            FROM `$connectdb`.`courses` c
            LEFT OUTER JOIN {connect_course} cc ON cc.module_delivery_key=c.module_delivery_key AND cc.session_code=c.session_code
            WHERE cc.id IS NULL AND c.session_code=:session_code AND c.module_delivery_key NOT LIKE \"%-%\"
            GROUP BY c.module_delivery_key,c.session_code,c.module_version
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * Updated Groups
     */
    public static function updated_groups() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating updated groups\n";

        $sql = "REPLACE INTO {connect_group} (`id`, `courseid`, `name`, `mid`) (
            SELECT g.group_id, c.id, g.group_desc, cg.mid
            FROM `$connectdb`.`groups` g
            INNER JOIN {connect_course} c ON c.module_delivery_key=g.module_delivery_key AND c.session_code=g.session_code
            INNER JOIN {connect_group} cg ON cg.id=g.group_id
            WHERE g.group_desc <> cg.name AND g.session_code=:session_code
            GROUP BY g.group_id
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * New Groups
     */
    public static function new_groups() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating new groups\n";

        $sql = "INSERT INTO {connect_group} (`id`, `courseid`, `name`, `mid`) (
            SELECT g.group_id, c.id, g.group_desc, g.moodle_id
            FROM `$connectdb`.`groups` g
            INNER JOIN {connect_course} c ON c.module_delivery_key=g.module_delivery_key AND c.session_code=g.session_code
            LEFT OUTER JOIN {connect_group} cg ON cg.id=g.group_id
            WHERE cg.id IS NULL AND g.session_code=:session_code
            GROUP BY g.group_id
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * Updated Enrolments
     */
    public static function updated_enrolments() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating updated enrolments\n";

        $sql = "REPLACE INTO {connect_enrolments} (`id`, `courseid`, `userid`, `roleid`,`deleted`) (
            SELECT ce.id, c.id, u.id, r.id, e.sink_deleted
            FROM `$connectdb`.`enrollments` e
            INNER JOIN {connect_course} c ON c.module_delivery_key=e.module_delivery_key AND c.session_code=e.session_code
            INNER JOIN {connect_user} u ON u.login=e.login
            INNER JOIN {connect_role} r ON r.name=e.role
            INNER JOIN {connect_enrolments} ce ON ce.courseid=c.id AND ce.userid=u.id AND ce.roleid=r.id
            WHERE e.sink_deleted <> ce.deleted AND e.session_code=:session_code
            GROUP BY ce.id
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * New Enrolments
     */
    public static function new_enrolments() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating new enrolments\n";

        $sql = "INSERT INTO {connect_enrolments} (`courseid`, `userid`, `roleid`,`deleted`) (
            SELECT c.id, u.id, r.id, e.sink_deleted
            FROM `$connectdb`.`enrollments` e
            INNER JOIN {connect_course} c ON c.module_delivery_key=e.module_delivery_key AND c.session_code=e.session_code
            INNER JOIN {connect_user} u ON u.login=e.login
            INNER JOIN {connect_role} r ON r.name=e.role
            LEFT OUTER JOIN {connect_enrolments} ce ON ce.courseid=c.id AND ce.userid=u.id AND ce.roleid=r.id
            WHERE ce.id IS NULL AND e.session_code=:session_code
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * Updated Group Enrolments
     */
    public static function updated_group_enrolments() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating updated group enrolments\n";

        $sql = "REPLACE INTO {connect_group_enrolments} (`id`, `groupid`, `userid`,`deleted`) (
            SELECT cge.id, ge.group_id, u.id, ge.sink_deleted
            FROM `$connectdb`.`group_enrollments` ge
            INNER JOIN {connect_user} u ON u.login=ge.login
            INNER JOIN {connect_group_enrolments} cge ON cge.groupid=ge.group_id AND cge.userid=u.id
            WHERE cge.deleted <> ge.sink_deleted AND ge.session_code=:session_code
            GROUP BY cge.id
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * New Group Enrolments
     */
    public static function new_group_enrolments() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating new group enrolments\n";

        $sql = "INSERT INTO {connect_group_enrolments} (`groupid`, `userid`,`deleted`) (
            SELECT ge.group_id, u.id, ge.sink_deleted
            FROM `$connectdb`.`group_enrollments` ge
            INNER JOIN {connect_user} u ON u.login=ge.login
            LEFT OUTER JOIN {connect_group_enrolments} cge ON cge.groupid=ge.group_id AND cge.userid=u.id
            WHERE cge.id IS NULL AND ge.session_code=:session_code
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * Map old roles to connect
     */
    public static function map_roles($dry = false) {
        global $DB;

        $roles = $DB->get_records('connect_role', array("mid" => 0));
        foreach ($roles as $role) {
            $obj = role::get($role->id);
            $data = $obj->get_data_mapping();

            // Try to find a matching role.
            $mid = $DB->get_field('role', 'id', array(
                'shortname' => $data['short']
            ));
            if ($mid !== false) {
                $role->mid = $mid;
                if (!$dry) {
                    $DB->update_record('connect_role', $role);
                }

                print "Mapped role {$role->name} to {$role->mid}.\n";
            }
        }
    }

    /**
     * Map old users to connect
     */
    public static function map_users($dry = false) {
        global $DB;

        $users = $DB->get_records('connect_user', array("mid" => 0));
        foreach ($users as $user) {
            // Try to find a matching user.
            $mid = $DB->get_field('user', 'id', array(
                'username' => $user->login
            ));
            if ($mid !== false) {
                $user->mid = $mid;
                if (!$dry) {
                    $DB->update_record('connect_user', $user);
                }

                print "Mapped user {$user->login} to {$user->mid}.\n";
            }
        }
    }

    /**
     * Port new rooms
     */
    public static function new_rooms() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating new rooms\n";

        $sql = "INSERT INTO {connect_room} (`campusid`, `name`) (
            SELECT tt.campus, tt.venue
            FROM `$connectdb`.`timetabling` tt
            LEFT OUTER JOIN {connect_room} ctt ON ctt.campusid=tt.campus AND ctt.name=tt.venue
            WHERE ctt.id IS NULL AND tt.session_code=:session_code
            GROUP BY tt.campus, tt.venue
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * Port new timetabling types
     */
    public static function new_timetabling_types() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating new timetabling types\n";

        $sql = "INSERT INTO {connect_type} (`name`) (
            SELECT tt.activity_type
            FROM `$connectdb`.`timetabling` tt
            LEFT OUTER JOIN {connect_type} cr ON cr.name=tt.activity_type
            WHERE ctt.id IS NULL AND tt.session_code=:session_code
            GROUP BY tt.activity_type
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * Port new timetabling information
     */
    public static function new_timetabling() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Migrating new timetabling information\n";

        $sql = "
        INSERT INTO {connect_timetabling} (`eventid`, `typeid`, `userid`, `courseid`, `roomid`, `starts`, `ends`, `day`, `weeks`) (
            SELECT tt.event_number, ct.id, cu.id, cc.id, cr.id, tt.activity_start, tt.activity_end, tt.activity_day, tt.weeks
            FROM `$connectdb`.`timetabling` tt
            INNER JOIN {connect_type} ct ON ct.name=tt.activity_type
            INNER JOIN {connect_user} cu ON cu.login=tt.login
            INNER JOIN {connect_course} cc
                ON cc.module_code=tt.module_code
                AND cc.module_title=tt.module_title
                AND cc.module_week_beginning=tt.module_week_beginning
                AND cc.campusid=tt.campus
            INNER JOIN {connect_room} cr ON cr.campusid=tt.campus AND cr.name=tt.venue

            LEFT OUTER JOIN {connect_timetabling} ctt
                ON ctt.eventid = tt.event_number
                AND ctt.userid = cu.id
                AND ctt.typeid = ct.id
                AND ctt.courseid = cc.id

            WHERE ctt.id IS NULL AND tt.session_code=:session_code
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }

    /**
     * Port updated timetabling information
     */
    public static function updated_timetabling() {
        global $DB, $CFG;

        $connectdb = $CFG->connect->db["name"];

        echo "Updating timetabling information\n";

        $sql = "
        REPLACE INTO {connect_timetabling} (`eventid`, `typeid`, `userid`, `courseid`, `roomid`, `starts`, `ends`, `day`, `weeks`) (
            SELECT tt.event_number, ct.id, cu.id, cc.id, cr.id, tt.activity_start, tt.activity_end, tt.activity_day, tt.weeks
            FROM `$connectdb`.`timetabling` tt
            INNER JOIN {connect_type} ct ON ct.name=tt.activity_type
            INNER JOIN {connect_user} cu ON cu.login=tt.login
            INNER JOIN {connect_course} cc
                ON cc.module_code=tt.module_code
                AND cc.module_title=tt.module_title
                AND cc.module_week_beginning=tt.module_week_beginning
                AND cc.campusid=tt.campus
            INNER JOIN {connect_room} cr ON cr.campusid=tt.campus AND cr.name=tt.venue

            INNER JOIN {connect_timetabling} ctt
                ON ctt.eventid = tt.event_number
                AND ctt.userid = cu.id
                AND ctt.typeid = ct.id
                AND ctt.courseid = cc.id

            WHERE
                tt.roomid <> cr.id
                OR tt.starts <> tt.activity_start
                OR tt.ends <> tt.activity_end
                OR tt.day <> tt.activity_day
                OR tt.weeks <> tt.weeks
        )";

        return $DB->execute($sql, array(
            "session_code" => $CFG->connect->session_code
        ));
    }
}
