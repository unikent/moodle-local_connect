$(document).ready(function() {


	var oTable = $('#datable').dataTable( {
		"bProcessing": true,
		"sAjaxSource": "arrays.txt",
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
			$('td:eq(0)', nRow).html('<div class="status_'+aData[0]+'">'+aData[0]+'</div');
			$(nRow).click(function() {
				$(this).toggleClass('row_selected');
			});	
		},
		"bPaginate" : false,

	}).columnFilter({
		aoColumns: [
			{
				type: 'checkbox',
				values: ['1', '2', '3', '4']
			},
			null,
			null,
			null,
			null,
		]
	});

	$('#dasearch input').keyup(function() {
		oTable.fnFilter($(this).val());
	});

	$('.status_checkbox').change(function() {
		if($(this).is(':checked')) {
			var val = $(this).val();
			$('#status_' + val).attr('checked','checked').trigger();
		} else {
			$('#status_' + val).removeAttr('checked');
		}
	});
});