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
  * Beta page JS.
  * 
  * @module local_connect/beta
  */
define([], function() {

	var getSelected = function() {
		return $("input[name='id']:checked");
	};

	var getName = function(id) {
		return $("tr.row-" + id).find('.c1').html();
	};

	var setCount = function() {
		var checked = getSelected();

		$("#job_number").html(checked.length);
	};

	var rebuildDeliverylist = function() {
		var checked = getSelected();

		if (checked.length > 0) {
			$("#display_list_toggle").show();
		}

		$("#jobs > ul").html("");

		$.each(checked, function(i, o) {
			$("#jobs > ul").append('<li>' + getName($(o).val()) + '</li>');
		});
	};

	var rebuildButtons = function() {
		var checked = getSelected();

		if (checked.length == 0) {
			$("#push_deliveries").attr("disabled", "disabled");
			$("#merge_deliveries").attr("disabled", "disabled");

			return;
		}

		if (checked.length == 1) {
			$("#push_deliveries").removeAttr("disabled");
			$("#merge_deliveries").attr("disabled", "disabled");

			return;
		}

		$("#push_deliveries").removeAttr("disabled");
		$("#merge_deliveries").removeAttr("disabled");
	};

	var onListChange = function() {
		setCount();
		rebuildDeliverylist();
		rebuildButtons();
	};

    return {
        init: function() {
        	$("#display_list_toggle").hide();
			$("#display_list_toggle").on('click', function() {
				$("#jobs > ul").toggle()
			});

        	$("input[name=id]").on('change', onListChange);

			$("#select_all").on('click', function() {
				$("input[name=id]:not(:checked)").trigger('click');
			});

			$("#deselect_all").on('click', function() {
				$("input[name=id]:checked").trigger('click');
			});

			require(['core/ajax', 'core/notification'], function(ajax, notification) {
				$("#push_deliveries").on('click', function() {
					var checked = getSelected();

					var calls = [];
					$.each(checked, function(i, o) {
						var id = $(o).val();

						calls.push({
			                methodname: 'local_connect_push_module',
			                args: {
			                    id: id
			                }
						})
					});

		            // Call web service once per delivery.
		            var promises = ajax.call(calls);
		            $.each(promises, function(i, o) {
			            promises[i].done(function(response) {
			            	$("tr.row-" + calls[i].args.id).remove();
			            	onListChange();
			            });

			            promises[i].fail(notification.exception);
		            });
				});

				$("#merge_deliveries").on('click', function() {
					var checked = getSelected().toArray();
					var primaryid = $(checked.shift()).val()

					var promises = ajax.call([{
		                methodname: 'local_connect_push_module',
		                args: {
		                    id: primaryid
		                }
					}]);

					promises[0].done(function(response) {
						// Remove from the list.
		            	$("tr.row-" + primaryid).remove();
		            	onListChange();

						// Great! Lets merge the rest.
						var calls = [];
						$.each(checked, function(i, o) {
							var id = $(o).val();

							calls.push({
				                methodname: 'local_connect_link_module',
				                args: {
				                    id: id,
				                    moodleid: response
				                }
							});
						});

			            // Call web service once per delivery.
			            var promises = ajax.call(calls);
			            $.each(promises, function(i, o) {
				            promises[i].done(function(response) {
				            	$("tr.row-" + calls[i].args.id).remove();
				            	onListChange();
				            });

				            promises[i].fail(notification.exception);
			            });
					});

					promises[0].fail(notification.exception);
				});
			});
        }
    };
});
