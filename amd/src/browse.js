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

/*
 * @package    local_connect
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * Browse page JS.
  * 
  * @module local_connect/browse
  */
define(['jquery', 'local_connect/jstree'], function($, jstree) {
    return {
        init: function() {
            $('#connect_browser').jstree({
                "core" : {
                    "animation" : 1,
                    "check_callback" : true,
                    "themes" : { "stripes" : true },
                    'data' : {
                        'url' : function (node) {
                            return M.cfg.wwwroot + "/local/connect/ajax/tree_data.php";
                        },
                        'data' : function (node) {
                            return { 'id' : node.id };
                        },
                        'dataType' : 'json'
                    }
                },
                "search" : {
                    "show_only_matches" : true,
                    "fuzzy" : false,
                    "ajax" : {
                        'url' : M.cfg.wwwroot + "/local/connect/ajax/tree_search.php",
                        'dataType' : 'json'
                    }
                },
                "plugins" : [
                    "search"
                ]
            });

            $('#connect_browser').on("changed.jstree", function (e, data) {
                if (data.node.a_attr.href != "#") {
                    window.location = data.node.a_attr.href;
                }
            });

            var to = false;
            $('#cb_search').keyup(function () {
                if (to) {
                    clearTimeout(to);
                }
                to = setTimeout(function () {
                    var v = $('#cb_search').val();
                    $('#connect_browser').jstree(true).search(v);
                }, 250);
            });
        }
    };
});