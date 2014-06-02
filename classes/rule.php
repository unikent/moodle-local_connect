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

require_once($CFG->libdir . "/accesslib.php");

/**
 * Connect rule container
 */
class rule extends data
{
    /**
     * The name of our connect table.
     */
    protected static function get_table() {
        return "connect_rules";
    }

    /**
     * A list of valid fields for this data object.
     */
    protected final static function valid_fields() {
        return array("id", "prefix", "category", "weight");
    }

    /**
     * A list of immutable fields for this data object.
     */
    protected static function immutable_fields() {
        return array("id");
    }

    /**
     * A list of key fields for this data object.
     */
    protected static function key_fields() {
        return array("id");
    }

    /**
     * Is this role in Moodle?
     * @return boolean
     */
    public function is_in_moodle() {
        return !empty($this->mid);
    }

    /**
     * Increase the priority of this rule (bumps it's weight up)
     */
    public function increase_priority() {
        $this->weight += 10;
        if ($this->weight > 100) {
            $this->weight = 100;
        }

        $this->save();
    }

    /**
     * Decrease the priority of this rule (bumps it's weight up)
     */
    public function decrease_priority() {
        $this->weight -= 10;
        if ($this->weight < 0) {
            $this->weight = 0;
        }

        $this->save();
    }

    /**
     * Delete this rule
     */
    public function delete() {
        global $DB;

        $DB->delete_records('connect_rules', array(
            "id" => $this->id
        ));
    }

    /**
     * Map a shortname or a course to a category.
     * 
     *   - Order by weight
     *   - If there is a matching rule with a weight higher than all others, return that
     *   - If there are multiple matching rules with the same weights, return the longest string
     */
    public static function map($obj) {
        global $DB;

        // Accept either an object or a string.
        if (is_object($obj)) {
            $obj = $obj->shortname;
        }

        // Map to variable.
        $shortname = $obj;

        // Grab all possible rules.
        $rules = $DB->get_records('connect_rules', null, 'weight DESC');

        // Go through and compare until we find the first one that matches.
        $maps = array();
        $matches = array();
        foreach ($rules as $rule) {
            if (strpos($shortname, $rule->prefix) === 0) {
                $matches[$rule->prefix] = $rule->weight;
                $maps[$rule->prefix] = $rule->category;
            }
        }

        // If we dont have any.
        if (count($matches) == 0) {
            return false;
        }

        // If we only have one.
        if (count($matches) == 1) {
            return $maps[key($matches)];
        }

        // Sort it.
        arsort($matches);

        $first = key($matches);

        // Is there a clear winner?
        $values = array_values($matches);
        if ($values[0] > $values[1]) {
            return $maps[$first];
        }

        // Nope, go through them and find the longest matching.
        $current = $first;
        $maxweight = $values[0];
        foreach ($matches as $prefix => $weight) {
            if ($weight < $maxweight) {
                break;
            }

            if (strlen($prefix) > strlen($current)) {
                $current = $prefix;
            }
        }

        return $maps[$first];
    }
}