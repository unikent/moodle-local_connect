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
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module local_connect/addlink
  */
define([], function() {
    var searchTimeout;

    /**
     * Setup the MDK search option for the form.
     */
    var setupForm = function() {
        $("#id_module_delivery_key")
            .addClass('form-control')
                .wrap('<div class="input-group">')
            .parent()
                .append('<span class="input-group-btn">' +
                        '<button class="btn btn-default" type="button" id="mdksearch">' +
                        '<i class="fa fa-search"></i></button></span>');
    };

    /**
     * Create the modal dialog.
     */
    var setupModal = function() {
        $("#mdksearch").on('click', function(e) {
            e.preventDefault();

            $('#searchmodal').modal();

            return false;
        });
    };

    /**
     * Setup listeners on the modal's form.
     */
    var setupListeners = function() {
        $("#searchmodalinput").on('keyup', function() {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            searchTimeout = setTimeout(doSearch, 1000);
        });
    };

    /**
     * Called when it's time to search.
     */
    var doSearch = function() {
        var val = $("#searchmodalinput").val();
        if (val.length < 4) {
            return;
        }

        // Show loading thing.
        $("#searchresults").html('<div style="text-align: center;"><i class="fa fa-spinner fa-spin"></i></div>');

        // AMD loader.
        require(['core/ajax', 'core/templates', 'core/notification'], function(ajax, templates, notification) {
            // Call AJAX webservice to search.
            var promises = ajax.call([{
                methodname: 'local_connect_search_modules',
                args: {
                    module_code: $("#searchmodalinput").val()
                }
            }])

            promises[0].done(function(response) {
                var courses = [];
                $.each(response, function(course) {
                    courses.push(response[course])
                });

                // Render a template.
                var promise = templates.render('local_connect/addlinktable', {'courses': courses});
                promise.done(function(source, javascript) {
                    $("#searchresults").html(source);
                });
                promise.fail(notification.exception);
            });

            promises[0].fail(notification.exception);
        });
    };

    return {
        init: function() {
            setupForm();
            setupModal();
            setupListeners();
        }
    };
});