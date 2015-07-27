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
 * Prints out JSON for a course list (DA pages).
 *
 * @deprecated Use local_connect_get_my web service instead!
 * @package    local_connect
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');

global $PAGE, $OUTPUT, $USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/connect/ajax/courses.php');

$restrictions = optional_param('category_restrictions', '', PARAM_RAW);

$courses = array();
if (!empty($restrictions)) {
    $restrictions = explode(',', $restrictions);

    // Check which categories we have permissions for.
    $categories = array();
    foreach ($restrictions as $category) {
        // Do we have access?
        $context = \context_coursecat::instance((int)$category);
        if (has_capability('moodle/category:manage', $context)) {
            $categories[] = $category;
        }
    }

    if (empty($categories)) {
        require_capability("local/connect:helpdesk", \context_system::instance());
    }

    $courses = \local_connect\course::get_by_category($categories);
} else {
    require_capability("local/connect:helpdesk", context_system::instance());
    $courses = \local_connect\course::get_all();
}

// Map campus IDs.
$campusids = array();
$campuses = $DB->get_records('connect_campus');
foreach ($campuses as $campus) {
    $campusids[$campus->id] = $campus->name;
}

// Try to map up merged modules.
$merged = array();
foreach ($courses as $course) {
    if (empty($course->mid)) {
        continue;
    }

    if (!isset($merged[$course->mid])) {
        $merged[$course->mid] = array();
    }

    $merged[$course->mid][] = $course;
}

$merged = array_filter($merged, function($a) {
    return count($a) > 1;
});

// Process everything.
$mergerefs = array();
$out = array();
foreach ($courses as $course) {
    $coursedata = $course->get_data();

    if (isset($campusids[$coursedata->campusid])) {
        $coursedata->campus = $campusids[$coursedata->campusid];
    }

    if (!isset($merged[$course->mid])) {
        $out[] = $coursedata;
        continue;
    }

    if (isset($mergerefs[$course->mid])) {
        $obj = $mergerefs[$course->mid];
        $obj->children[] = $coursedata;
        continue;
    }

    // This is a merged module, create a skeleton.
    $merge = clone($coursedata);
    $merge->module_title = $course->shortname;
    $merge->campus_desc = $course->campus_name;
    $merge->children = array($coursedata);

    $mergerefs[$course->mid] = $merge;

    $out[] = $merge;
}

echo $OUTPUT->header();
echo json_encode($out);