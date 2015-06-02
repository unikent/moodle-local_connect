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
 * Moodle provisioner.
 *
 * @package    local_connect
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        // Dry run pls.
        'dry' => false
    )
);

raise_memory_limit(MEMORY_HUGE);

$username = exec('logname');
$user = $DB->get_record('user', array(
    'username' => $username
));

if (!$user) {
    $user = get_admin();
}

\core\session\manager::set_user($user);

echo "Building task tree...";
$provisioner = new \local_connect\provisioner\base();
if ($provisioner->prepare()) {
    if (!isset($options['dry']) || !$options['dry']) {
        echo "Done!\nExecuting... ";
        $provisioner->execute();
    } else {
        $tree = $provisioner->get_tree();
        echo "$tree\n";
        echo "Total: " . $tree->count_children() . " actions.\n";
    }
}
