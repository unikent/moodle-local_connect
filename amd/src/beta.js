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

	}

    return {
        init: function() {
        	$("#display_list_toggle").hide();
			$("#display_list_toggle").on('click', function() {
				$("#jobs > ul").toggle()
			});

        	$("input[name=id]").on('change', function() {
        		setCount();
        		rebuildDeliverylist();
        	});
        }
    };
});
