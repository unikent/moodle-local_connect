$(document).ready(function() {

	function statusbox(el, message) {
		var position = $(el).position();
		if($('#statusbox').is(':visible') && $('#statusbox').position().top === position.top) {
			return
		}

		// kill a statusHide timeout if it exists
		if (statusHide) clearTimeout(statusHide);

		$('#statusbox').stop(true, true).html(message).css({
			'top' : position.top,
		}).click(function() {
			$(this).hide();
			clearTimeout(statusHide);
		}).fadeIn('fast');//.fadeIn('fast').delay(500).fadeOut('fast');

		statusHide = setTimeout(function() {
			$('#statusbox').fadeOut('fast');
		}, 5000)
	}

	var status = [];
	var statusHide = null;

	var oTable = $('#datable').dataTable( {
		"bProcessing": true,
		"sAjaxSource": window.dapageUrl + 'da-mock.php',
		"aoColumns": [
			{'sClass': 'status'},
			{'sClass': 'code'},
			{'sClass': 'name'},
			{'sClass': 'campus'},
			{'sClass': 'duration'},
		],
		"oLanguage": {
			"sSearch": "Search all columns:"
		},
		"fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull){
			var name = aData[0].split('_').join(' ');
			$('td:eq(0)', nRow).html('<div class="status_'+aData[0]+'">'+name+'</div');
			$('#statusbox').fadeOut('fast');

		},
		"fnCreatedRow": function(nRow, aData, iDataIndex) {
			var name = aData[0].split('_').join(' ');
			$(nRow).click(function() {
				if(aData[0] === 'unprocessed' || aData[0] === 'failed_in_moodle') {
					if($(this).hasClass('row_selected')) {
						$(this).removeClass('row_selected');
					} else {
						$(this).addClass('row_selected');
					}
				} else {
					statusbox (nRow, 'Error: you cannot push a delivery with a status of ' + name);
				}
			});
		},
		"bPaginate" : false,

		fnInitComplete: function(oSettings, json) {

			$('#datable tbody tr').click(function() {

				//console.log(oTable.fnGetNodes());
				var count = 0;
				$('#datable tbody tr.row_selected').each(function() {
					count++;
				});

				$('#jobs').html(count);
			});
			

			$('#datable_wrapper').prepend('<div id="statusbox"></div>');
			$('#statusbox').hide();
			status = _.uniq(_.map(json.aaData, function(val) { return val[0]; }));

			oTable.columnFilter({
				aoColumns: [
					{
						type: 'checkbox',
						values: status
					},
					null,
					null,
					null,
					null,
				]
			});

			$(status).each(function(val) {
				var spaces = [];
				spaces[val] = status[val].split('_').join(' ');
				$('<li><input type="checkbox" name="'+status[val]+'" value="'+status[val]+'"  id="'+status[val]+'" class="status_checkbox"/><label id="label-'+status[val]+'" for="'+status[val]+'">'+spaces[val]+'</label></li>').appendTo('#status_toggle');
			});
			
			$('#dasearch input').keyup(function() {
					oTable.fnFilter($(this).val());
			});

			$('#unprocessed').attr('checked', 'checked');
			$('#status_unprocessed').attr('checked','checked').trigger('change');

			$('.status_checkbox').change(function() {
				if($(this).is(':checked')) {
					var val = $(this).val();
					$('#status_' + val).attr('checked','checked').trigger('change');
				} else {
					var val = $(this).val();
					$('#status_' + val).removeAttr('checked').trigger('change');
				}
			});
		}
	});

	
});