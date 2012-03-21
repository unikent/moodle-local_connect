$(document).ready(function() {


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
			$('td:eq(0)', nRow).html('<div class="status_'+aData[0]+'">'+aData[0]+'</div');
			/*$(nRow).click(function() {
				if($(this).hasClass('row_selected')) {
					$(this).removeClass('row_selected');
				} else {
					$(this).addClass('row_selected');
				}
			});	*/
		},
		"fnCreatedRow": function(nRow, aData, iDataIndex) {
			console.log('bob');
			$(nRow).click(function() {
				if($(this).hasClass('row_selected')) {
					$(this).removeClass('row_selected');
				} else {
					$(this).addClass('row_selected');
				}
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
			$('#status_' + val).attr('checked','checked').trigger('change');
		} else {
			var val = $(this).val();
			$('#status_' + val).removeAttr('checked').trigger('change');
		}
	});
});