$.blockUI({ message: '<div class="blockui_loading">Please wait, loading course lists.</div>' });
$("#dialog_error").dialog({
     autoOpen: false,
     title: 'Connect error',
     modal: true,
     buttons: {
        "OK": function() {
            $(this).dialog("close");
        }
     }
});

$('#dialog-confirm').dialog({
	autoOpen: false,
	height: 185,
	width: 375,
	modal: true
});

$( "#dialog-form" ).dialog({
	autoOpen: false,
	height: 500,
	width: 600,
	modal: true,
	buttons: {
		"Push to moodle": function() {
		},
		Cancel: function() {
			$( this ).dialog( "close" );
		},
	},
	close: function() {
		//allFields.val( "" ).removeClass( "ui-state-error" );
	}
});


if(window.cats === '') {
		data = {};

	} else {
		data = {
			category_restrictions:window.cats
		};
}

$.ajax({
		url: window.dapageUrl + '/courses/',
		type: 'GET',
		data: data,
		success: function(json) {
			kenConnect = new Connect({
				tabledata: json,
				tableEl: $('#datable'),
				statusEl: $('.status_checkbox'),
				searchEl: $('#dasearch input'),
				buttons: {
					rowsSel: '.parent',
					childSel: '.child_expand',
					unlinkRowSel: '.unlink_row',
					unlinkChildSel: '.unlink_child',
					pushBtn: $('#push_deliveries'),
					mergeBtn: $('#merge_deliveries'),
					selAll: $('#select_all'),
					deSelAll: $('#deselect_all'),
					edit: $('.edit_row'),
					listToggle: $('#display_list_toggle')
				},
				formEl: {
					notes: $('#edit_notifications'),
					shortName: $('#shortname'),
					fullName: $('#fullname'),
					synopsis: $('#synopsis'),
					cat: $('#category'),
					shrtNmExtTd: $('#shortname_ext_td'),
					shortNameExt: $('#shortname_ext')
				}
			});
		},
		error: function(event) {
			jQuery.unblockUI();
	        $("#dialog_error").dialog("open");
		}
}); 

var $scrolldiv = $('#jobs_wrapper');

$(window).scroll(function() {
	$scrolldiv.stop().css({
		'marginTop': ($(window).scrollTop()) + 'px'

	}, 'fast');
})