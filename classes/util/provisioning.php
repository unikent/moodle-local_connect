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

        // Create it if we can.

        // Merge it if we can't just create it.

        // Append AUT,SPR,SUM if we can't.
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
                print "Error: duplicate module {$record->id}: {$uid}!";
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
            $deliverykey = $merger[0];
            $code = $merger[1];
            $weekbeginning = $merger[2];
            $length = $merger[3];
            $version = $merger[4];
            $parentid = $merger[5];

            if (!isset($this->mergers[$parentid])) {
                $this->mergers[$parentid] = array();
            }

            $this->mergers[$parentid][] = array(
                "module_delivery_key" => $deliverykey,
                "module_code" => $code,
                "module_week_beginning" => $weekbeginning,
                "module_length" => $length,
                "module_version" => $version
            );
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