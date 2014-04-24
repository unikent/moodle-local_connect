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
 * This removes all old sessions from Memcached.
 * It is run separately from the Moodle cron, one per night.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2013 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/cronlib.php');

// Do we have anything to do?
if (!isset($CFG->session_handler_class) || $CFG->session_handler_class !== '\core\session\memcached') {
    return false;
}

$start_time = strftime("%H:%M %d-%m-%Y");
print "\nStarting session cleanup at $start_time.\n";

global $CFG;

// Split up the save path
$servers = array();
$parts   = explode(',', $CFG->session_memcached_save_path);
foreach ($parts as $part) {
    $part = trim($part);
    $pos  = strrpos($part, ':');
    $host = substr($part, 0, $pos);
    $port = substr($part, ($pos + 1));
    $servers[] = array($host, $port);
}

// Boot up memcached
$memcache = new \Memcache();
foreach ($servers as $server) {
    $memcache->addServer($server[0], $server[1]);
}

// Grab a list of all current sessions (Moodle handles GC... this is for cleaning up Memcached)
$valid_sessions = array();
global $DB;
$sql = "SELECT s.sid FROM {sessions} s";
$rs = $DB->get_recordset_sql($sql);
foreach ($rs as $session) {
    $valid_sessions[$session->sid] = true;
}

$session_count = count($valid_sessions);
print "Currently $session_count active sessions.\n";

// Cleanup all old sessions that exist in Memcached
$count = 0;
$allSlabs = $memcache->getExtendedStats('slabs');
$items = $memcache->getExtendedStats('items');
foreach ($allSlabs as $server => $slabs) {
    foreach ($slabs as $slabId => $slabMeta) {
        if (!is_int($slabId)) {
            continue;
        }

        $cdump = $memcache->getExtendedStats('cachedump', (int) $slabId, 100000000);
        foreach ($cdump as $server => $entries) {
            if ($entries) {
                foreach ($entries as $eName => $eData) {
                    // Is this a session key?
                    if (strpos($eName, $CFG->session_memcached_prefix) !== 0) {
                        continue;
                    }

                    // Extract the session key
                    $sesskey = substr($eName, strlen($CFG->session_memcached_prefix));

                    // Is this a lock?
                    if (strpos($sesskey, "lock.") === 0) {
                        $sesskey = substr($sesskey, strlen("lock."));
                    }

                    // Is this valid?
                    if (isset($valid_sessions[$sesskey])) {
                        continue;
                    }

                    $memcache->delete($eName);
                    $count++;
                }
            }
        }
    }
}

// Halve count (locks + data)
$count = $count / 2;

print "Cleaned up $count sessions.\n";