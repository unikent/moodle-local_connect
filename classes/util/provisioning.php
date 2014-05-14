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

namespace local_connect\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for Moodle provisioning
 */
class provisioning
{
    /** A list of modules that we have to do something with. */
    private $modules;

    /** A list of modules that were mergers last year. */
    private $mergers;

    /**
     * Constructor
     */
    public function __construct() {
        $this->modules = array();
        $this->mergers = array();
    }

    /**
     * The place this all starts.
     */
    public function go() {
        // First, we grab a list of courses.
        $this->build_modules();

        // Then, we grab a list of mergers from last year.
        $this->build_matches();

        // Find matches between the module and merger list.
        $matches = $this->match_mergers();

        // Create it if we can.
        $this->handle_mergers($matches);

        // Right. Now. What's left?
        // We want to start by grabbing everything with a unique shortcode and creating it.
        $this->handle_unique();
    }

    /**
     * A log of errors.
     */
    private function error($message) {
        echo "[Error] $message\n";
    }

    /**
     * A log of actions.
     */
    private function log($message) {
        echo "[Action] $message\n";
    }

    /**
     * Create a course (also handles automatic shortnameext).
     */
    private function create_course($course) {
        $shortnameext = "";
        if (!$course->is_unique_shortname($course->shortname)) {
            if ($course->module_week_beginning == 1) {
                $shortnameext = "AUT";
            }
            if ($course->module_week_beginning == 13) {
                $shortnameext = "SPR";
            }
            if ($course->module_week_beginning == 25) {
                $shortnameext = "SUM";
            }

            if (empty($shortnameext)) {
                $this->error("Could not find suitable shortnameext for course '{$course->id}'.");
                return false;
            }
        }

        $result = $course->create_in_moodle($shortnameext);

        // Log it.
        if ($result) {
            $this->log("Created course '{$course->id}'.");
        } else {
            $this->error("Error creating course '{$course->id}'!");
        }

        return $result;
    }

    /**
     * Create unique modules.
     */
    private function handle_unique() {
        global $DB;

        $sql = <<<SQL
        SELECT *
        FROM {connect_course} c
        GROUP BY module_code
        HAVING COUNT(c.id) = 1
SQL;

        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $course) {
            $course = \local_connect\course::from_sql_result($course);
            if (!$course->is_in_moodle()) {
                $this->create_course($course);
            }
        }
        $rs->close();
    }

    /**
     * Handles the creation of merged modules.
     */
    private function handle_mergers($matches) {
        foreach ($matches as $match) {
            $courses = array();
            foreach ($match as $key) {
                $courses[] = $this->modules[$key];

                // Don't do anything else with this.
                unset($this->modules[$key]);
            }

            // Take the primary course.
            $primary = array_shift($courses);
            $primary = \local_connect\course::get($primary->id);

            // Create the primary.
            if (!$primary->is_in_moodle()) {
                if (!$this->create_course($primary)) {
                    continue;
                }
            }

            // Add children.
            foreach ($courses as $course) {
                $course = \local_connect\course::get($course->id);
                if (!$course->is_in_moodle()) {
                    $primary->add_child($course);
                    $this->log("Mapped course '{$course->id}' to Moodle course '{$primary->mid}'.");
                }
            }
        }
    }

    /**
     * Matches things in modules with mergers.
     */
    private function match_mergers() {
        $matches = array();

        foreach ($this->mergers as $k => $v) {
            if (isset($this->modules[$k])) {
                if (!isset($matches[$v])) {
                    $matches[$v] = array();
                }
                $matches[$v][] = $k;
            }
        }

        // Strip out singles.
        $matches = array_filter($matches, function($entry) {
            return count($entry) > 1;
        });

        return array_values($matches);
    }

    /**
     * Given some values, strings up what should be a UID.
     */
    private function get_uid($code, $version, $weekbeginning, $length, $campusid) {
        return $code . "_" . $version . "_" . $weekbeginning . "_" . $length . "_" . $campusid;
    }

    /**
     * Grabs a list of courses.
     */
    private function build_modules() {
        global $DB;

        $rs = $DB->get_recordset('connect_course');
        foreach ($rs as $record) {
            $uid = $this->get_uid($record->module_code, $record->module_version, $record->module_week_beginning,
                                  $record->module_length, $record->campusid);
            if (isset($this->modules[$uid])) {
                $this->error("Duplicate module found: '{$record->id}' - '{$uid}'!");
                continue;
            }

            $this->modules[$uid] = $record;
        }
        $rs->close();
    }

    /**
     * Grabs a list of matches from last year.
     */
    private function build_matches() {
        $mergers = $this->get_csv_data();

        foreach ($mergers as $merger) {
            $code = $merger[0];
            $version = $merger[1];
            $weekbeginning = $merger[2];
            $length = $merger[3];
            $campusid = $merger[4];
            $parentid = $merger[5];

            $uid = $this->get_uid($code, $version, $weekbeginning, $length, $campusid);

            if (isset($this->mergers[$uid])) {
                $this->error("Error: duplicate module '{$uid}'!");
                continue;
            }

            $this->mergers[$uid] = $parentid;
        }
    }

    /**
     * Returns the contents of a CSV.
     */
    private function get_csv_data() {
        global $CFG;

        require_once($CFG->libdir . '/csvlib.class.php');

        $lastyear = (int)$CFG->connect->session_code - 2;
        $filename = $CFG->dirroot . "/local/connect/db/data/" . $lastyear . "_mergers.csv";
        if (!file_exists($filename)) {
            print_error("No information on last year's mergers found in {$filename}!");
        }

        $contents = file_get_contents($filename);
        $importid = \csv_import_reader::get_new_iid('connectprovisioner');
        $cir = new \csv_import_reader($importid, 'connectprovisioner');
        $readcount = $cir->load_csv_content($contents, 'utf-8', ',');

        if ($readcount <= 0) {
            print_error("Couldnt read file {$filename}!");
        }

        $cir->init();
        $lines = array();
        while ($line = $cir->next()) {
            $lines[] = $line;
        }
        $cir->close();
        unset($contents);

        return $lines;
    }
}