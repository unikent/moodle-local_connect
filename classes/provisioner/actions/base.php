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

namespace local_connect\provisioner\actions;

defined('MOODLE_INTERNAL') || die();

/**
 * Moodle provisioning toolkit.
 * Action base.
 *
 * @since Moodle 2015
 */
class base
{
    /**
     * List of sub-actions.
     *
     * @internal
     */
    private $_children;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->_children = array();
    }

    /**
     * Add a child action.
     */
    public function add_child($action) {
        return $this->_children[] = $action;
    }

    /**
     * Return all children.
     */
    public function get_children() {
        return $this->_children;
    }

    /**
     * Tree map.
     */
    public function map($func) {
        $func($this);
        foreach ($this->_children as $child) {
            $child->map($func);
        }
    }

    /**
     * Tree filter.
     */
    public function filter($func) {
        foreach ($this->_children as $k => $child) {
            if (!$func($child)) {
                unset($this->_children[$k]);
            } else {
                $child->filter($func);
            }
        }
    }

    /**
     * Returns the entire tree below this base node.
     */
    public function get_flat_tree() {
        $tree = array($this);
        foreach ($this->_children as $child) {
            $tree = array_merge($tree, $child->get_flat_tree());
        }
        return $tree;
    }

    /**
     * Execute this action.
     */
    public function execute() {
        $total = count($this->_children);
        $i = 0;
        $lastout = -1;
        foreach ($this->_children as $child) {
            $child->execute();

            $percent = floor(($i / $total) * 100);
            if ($percent % 10 === 0 && $percent != $lastout) {
                echo "{$percent}%...";
                $lastout = $percent;
            }

            $i++;
        }

        if ($i > 0) {
            echo "\n";
        }
    }

    /**
     * toString override.
     */
    public function __toString() {
        return implode("\n", $this->get_children());
    }
}
