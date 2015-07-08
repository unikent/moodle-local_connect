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
 * Local lib code
 *
 * @package    local_connect
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function local_connect_extend_settings_navigation(settings_navigation $nav, context $context) {
    global $DB, $PAGE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course || $PAGE->course->id == 1 || !\local_connect\util\helpers::is_enabled()) {
        return null;
    }

    // Check we can update the course.
    $context = \context_course::instance($PAGE->course->id);
    if (!has_capability('moodle/course:update', $context)) {
        return null;
    }

    // Add an "SDS Links" nav item.
    if ($settingnode = $nav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $url = new moodle_url('/local/connect/manage/course.php', array(
            'mid' => $PAGE->course->id
        ));

        $sdsnode = $settingnode->add('SDS Links', $url, navigation_node::TYPE_CONTAINER);

        $url = new moodle_url('/local/connect/manage/addlink.php', array(
            'mid' => $PAGE->course->id
        ));

        $node = navigation_node::create(
            'Add link',
            $url,
            navigation_node::NODETYPE_LEAF,
            'local_connect',
            'local_connect',
            new pix_icon('e/insert_edit_link', 'Add link')
        );

        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
            $node->make_active();
        }

        $sdsnode->add_node($node);

        return $node;
    }
}