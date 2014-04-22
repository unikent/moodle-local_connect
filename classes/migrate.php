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
 * Connect migration scripts
 */
class migrate {
	/**
	 * Run all of them.
	 */
	public static function all() {
		self::new_users();
		self::new_campus();
		self::updated_courses();
		self::new_courses();
		self::updated_groups();
		self::new_groups();
		self::updated_enrolments();
		self::new_enrolments();
		self::updated_group_enrolments();
		self::new_group_enrolments();
	}

	/**
	 * New Users
	 */
	public static function new_users() {
		global $DB;

		echo "Migrating new users\n";

		$sql = "REPLACE INTO {connect_user} (ukc, login, title, initials, family_name) (
			SELECT e.ukc, e.login, e.title, e.initials, e.family_name
			FROM `connect_2013`.`enrollments` e
			LEFT OUTER JOIN {connect_user} u
				ON u.login=e.login
			WHERE u.id IS NULL
			GROUP BY e.login
		);";

		return $DB->execute($sql);
	}

	/**
	 * New Campuses
	 */
	public static function new_campus() {
		global $DB;

		echo "Migrating new campus\n";

		$sql = "REPLACE INTO {connect_campus} (`id`, `name`) (
			SELECT c.campus, c.campus_desc
			FROM `connect_2013`.`courses` c
			LEFT OUTER JOIN {connect_campus} cc
				ON cc.id=c.campus
			WHERE cc.id IS NULL
			GROUP BY c.campus
		);";

		return $DB->execute($sql);
	}

	/**
	 * Updated Courses
	 */
	public static function updated_courses() {
		global $DB;

		echo "Migrating updated courses\n";

		$sql = "REPLACE INTO {connect_course} (id,module_delivery_key,session_code,module_version,campusid,module_week_beginning,module_length,week_beginning_date,module_title,module_code,synopsis,category, mid) (
			SELECT cc.id, c.module_delivery_key,c.session_code,COALESCE(c.module_version,1),c.campus as campusid,c.module_week_beginning,c.module_length,c.week_beginning_date,c.module_title,c.module_code,COALESCE(c.synopsis, ''),c.category_id,cc.mid
			FROM `connect_development`.`courses` c
			INNER JOIN {connect_course} cc ON cc.module_delivery_key=c.module_delivery_key AND cc.session_code=c.session_code
			WHERE c.module_title <> cc.module_title OR c.module_code <> cc.module_code OR c.synopsis <> cc.synopsis OR c.category_id <> cc.category
			GROUP BY c.module_delivery_key,c.session_code,c.module_version
		);";

		return $DB->execute($sql);
	}

	/**
	 * New Courses
	 */
	public static function new_courses() {
		global $DB;

		echo "Migrating new courses\n";

		$sql = "REPLACE INTO {connect_course} (module_delivery_key,session_code,module_version,campusid,module_week_beginning,module_length,week_beginning_date,module_title,module_code,synopsis,category, mid) (
			SELECT c.module_delivery_key,c.session_code,COALESCE(c.module_version,1),c.campus as campusid,c.module_week_beginning,c.module_length,c.week_beginning_date,c.module_title,c.module_code,COALESCE(c.synopsis, ''),c.category_id,c.moodle_id as mid
			FROM `connect_2013`.`courses` c
			LEFT OUTER JOIN {connect_course} cc ON cc.module_delivery_key=c.module_delivery_key AND cc.session_code=c.session_code
			WHERE cc.id IS NULL
			GROUP BY c.module_delivery_key,c.session_code,c.module_version
		);";

		return $DB->execute($sql);
	}

	/**
	 * Updated Groups
	 */
	public static function updated_groups() {
		global $DB;

		echo "Migrating updated groups\n";

		$sql = "REPLACE INTO {connect_group} (`id`, `courseid`, `name`, `mid`) (
			SELECT g.group_id, c.id, g.group_desc, cg.mid
			FROM `connect_development`.`groups` g
			INNER JOIN {connect_course} c ON c.module_delivery_key=g.module_delivery_key AND c.session_code=g.session_code
			INNER JOIN {connect_group} cg ON cg.id=g.group_id
			WHERE g.group_desc <> cg.name
			GROUP BY g.group_id
		);";

		return $DB->execute($sql);
	}

	/**
	 * New Groups
	 */
	public static function new_groups() {
		global $DB;

		echo "Migrating new groups\n";

		$sql = "REPLACE INTO {connect_group} (`id`, `courseid`, `name`, `mid`) (
			SELECT g.group_id, c.id, g.group_desc, g.moodle_id
			FROM `connect_2013`.`groups` g
			INNER JOIN {connect_course} c ON c.module_delivery_key=g.module_delivery_key AND c.session_code=g.session_code
			LEFT OUTER JOIN {connect_group} cg ON cg.id=g.group_id
			WHERE cg.id IS NULL
			GROUP BY g.group_id
		);";

		return $DB->execute($sql);
	}

	/**
	 * Updated Enrolments
	 */
	public static function updated_enrolments() {
		global $DB;

		echo "Migrating updated enrolments\n";

		$sql = "REPLACE INTO {connect_enrolments} (`id`, `courseid`, `userid`, `roleid`,`deleted`) (
			SELECT ce.id, c.id, u.id, r.id, e.sink_deleted
			FROM `connect_development`.`enrollments` e
			INNER JOIN {connect_course} c ON c.module_delivery_key=e.module_delivery_key AND c.session_code=e.session_code
			INNER JOIN {connect_user} u ON u.login=e.login
			INNER JOIN {connect_role} r ON r.name=e.role
			INNER JOIN {connect_enrolments} ce ON ce.courseid=c.id AND ce.userid=u.id AND ce.roleid=r.id
			WHERE e.sink_deleted <> ce.deleted
			GROUP BY ce.id
		);";

		return $DB->execute($sql);
	}

	/**
	 * New Enrolments
	 */
	public static function new_enrolments() {
		global $DB;

		echo "Migrating new enrolments\n";

		$sql = "REPLACE INTO {connect_enrolments} (`courseid`, `userid`, `roleid`,`deleted`) (
			SELECT c.id, u.id, r.id, e.sink_deleted
			FROM `connect_2013`.`enrollments` e
			INNER JOIN {connect_course} c ON c.module_delivery_key=e.module_delivery_key AND c.session_code=e.session_code
			INNER JOIN {connect_user} u ON u.login=e.login
			INNER JOIN {connect_role} r ON r.name=e.role
			LEFT OUTER JOIN {connect_enrolments} ce ON ce.courseid=c.id AND ce.userid=u.id AND ce.roleid=r.id
			WHERE ce.id IS NULL
		);";

		return $DB->execute($sql);
	}

	/**
	 * Updated Group Enrolments
	 */
	public static function updated_group_enrolments() {
		global $DB;

		echo "Migrating updated group enrolments\n";

		$sql = "REPLACE INTO {connect_group_enrolments} (`id`, `groupid`, `userid`,`deleted`) (
			SELECT cge.id, ge.group_id, u.id, ge.sink_deleted
			FROM `connect_2013`.`group_enrollments` ge
			INNER JOIN {connect_user} u ON u.login=ge.login
			INNER JOIN {connect_group_enrolments} cge ON cge.groupid=ge.group_id AND cge.userid=u.id
			WHERE cge.deleted <> ge.sink_deleted
			GROUP BY cge.id
		);";

		return $DB->execute($sql);
	}

	/**
	 * New Group Enrolments
	 */
	public static function new_group_enrolments() {
		global $DB;

		echo "Migrating new group enrolments\n";

		$sql = "REPLACE INTO {connect_group_enrolments} (`groupid`, `userid`,`deleted`) (
			SELECT ge.group_id, u.id, ge.sink_deleted
			FROM `connect_2013`.`group_enrollments` ge
			INNER JOIN {connect_user} u ON u.login=ge.login
			LEFT OUTER JOIN {connect_group_enrolments} cge ON cge.groupid=ge.group_id AND cge.userid=u.id
			WHERE cge.id IS NULL
		);";

		return $DB->execute($sql);
	}
}
