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

    /** Are we in dry mode? */
    private $dry;

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
    public function go($dry = false) {
        $this->dry = $dry === true;
        if ($this->dry) {
            echo "Running in dry mode.\n";
        }

        // First, we grab a list of courses.
        echo "Building Modules...\n";
        $this->build_modules();

        // Then, we grab a list of mergers from last year.
        echo "Building Matches...\n";
        $this->build_matches();

        // Find matches between the module and merger list.
        echo "Generating Match List...\n";
        $matches = $this->match_mergers();

        // Create it if we can.
        echo "Processing Match List...\n";
        $this->handle_mergers($matches);

        // Right. Now. What's left?
        // We want to start by grabbing everything with a unique shortcode and creating it.
        echo "Processing Unique Modules...\n";
        $this->handle_unique();

        // Right. Now. What's left? #2.
        echo "Processing Potential Mergers...\n";
        $this->handle_remaining_mergers();
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
     * Does the given course have any enrolments?
     */
    private function has_enrolments($course) {
        global $DB;

        return $DB->count_records('connect_enrolments', array(
            "courseid" => $course->id,
            "deleted" => 0
        )) > 0;
    }

    /**
     * Get the ID for Canterbury campus.
     */
    private function get_canterbury() {
        global $DB;

        static $id = null;
        if ($id === null) {
            $id = $DB->get_field('connect_campus', 'id', array(
                'name' => 'Canterbury'
            ));
        }

        return $id;
    }


    /**
     * Get the ID for Medway campus.
     */
    private function get_medway() {
        global $DB;

        static $id = null;
        if ($id === null) {
            $id = $DB->get_field('connect_campus', 'id', array(
                'name' => 'Medway'
            ));
        }

        return $id;
    }

    /**
     * Create a course (also handles automatic shortnameext).
     */
    private function create_course($course, $strict = true) {
        global $DB;

        $shortnameext = "";

        if (strpos($course->module_code, "WSHOP") === 0) {
            if (!$course->is_unique_shortname($course->shortname, $strict)) {
                $shortnameext = "(week " . $course->module_week_beginning . ")";
                $course->set_shortname_ext($shortnameext);
            }
        }

        if (!$course->is_unique_shortname($course->shortname, $strict)) {
            if ($course->module_week_beginning == 1) {
                $shortnameext = "AUT";
            }
            if ($course->module_week_beginning >= 12) {
                $shortnameext = "SPR";
            }
            if ($course->module_week_beginning >= 24) {
                $shortnameext = "SUM";
            }

            if (empty($shortnameext)) {
                $this->error("Could not find suitable date shortnameext for course '{$course->id}'.");
                return false;
            }

            $course->set_shortname_ext($shortnameext);
        }

        // Make sure we are still unique.
        if (!$course->is_unique_shortname($course->shortname, $strict)) {
            $canterbury = $this->get_canterbury();
            $medway = $this->get_medway();

            if ($course->campusid !== $canterbury && $course->campusid !== $medway) {
                // Append the campus name too.
                $campus = $DB->get_field('connect_campus', 'name', array(
                    'id' => $course->campusid
                ));

                $shortnameext = "{$shortnameext} ({$campus})";
                $course->set_shortname_ext($shortnameext);
            } else {
                $this->error("Could not find suitable shortnameext for course '{$course->id}'.");
                return false;
            }
        }

        $result = true;
        if (!$this->dry) {
            $result = $course->create_in_moodle();
        }

        // Log it.
        if ($result) {
            $this->log("Created course '{$course->id}'.");
        } else {
            $this->error("Error creating course '{$course->id}'!");
        }

        return $result;
    }

    /**
     * Can we match this course to an existing course?
     */
    private function find_match($course) {
        global $DB;

        $canterbury = $this->get_canterbury();
        $medway = $this->get_medway();

        // We match on everything relevant.
        $matches = $DB->get_records('connect_course', array(
            'module_code' => $course->module_code,
            'module_week_beginning' => $course->module_week_beginning,
            'module_length' => $course->module_length,
            'category' => $course->category
        ));

        foreach ($matches as $match) {
            if ($match->mid > 0) {
                if ($course->campusid === $canterbury || $course->campusid === $medway) {
                    if ($match->campusid === $canterbury || $match->campusid === $medway) {
                        // We have a match!
                        return $match;
                    }
                }

                if ($match->campusid === $course->campusid) {
                    // We have a match!
                    return $match;
                }
            }
        }

        return false;
    }

    /**
     * What is left?
     */
    private function handle_remaining_mergers() {
        global $DB;

        $rs = $DB->get_recordset_select('connect_course', 'mid IS NULL or mid=0');
        foreach ($rs as $data) {
            if (!$this->has_enrolments($data)) {
                continue;
            }

            $course = \local_connect\course::from_sql_result($data);

            $match = $this->find_match($data);
            if ($match !== false) {
                $match = \local_connect\course::from_sql_result($match);
                $match->add_child($course);
                $this->log("Mapped course '{$course->id}' to Moodle course '{$match->mid}'.");
                continue;
            }

            $this->create_course($course, false);
        }
        $rs->close();
    }

    /**
     * Create unique modules.
     */
    private function handle_unique() {
        global $DB;

        $sql = <<<SQL
        SELECT *
        FROM {connect_course} c
        WHERE c.mid IS NULL OR c.mid = 0
        GROUP BY module_code
        HAVING COUNT(c.id) = 1
SQL;

        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $course) {
            if (!$this->has_enrolments($course)) {
                continue;
            }

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
                if (!$this->create_course($primary, false)) {
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