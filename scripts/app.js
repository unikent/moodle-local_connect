$.blockUI({ message: '<div class="blockui_loading">Please wait, loading module lists.</div>' });
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

function connect_load(yui, cats) {
	if (cats === '') {
		data = {};
	} else {
		data = {
			category_restrictions: cats
		};
	}
	console.log(data);

	$.ajax({
			url: M.cfg.wwwroot + '/local/connect/proxy.php/courses/',
			type: 'GET',
			data: data,
			dataType: 'json',
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
						listToggle: $('#display_list_toggle'),
						pageRefresh: $('.data_refresh')
					},
					formEl: {
						notes: $('#edit_notifications'),
						shortName: $('#shortname'),
						fullName: $('#fullname'),
						synopsis: $('#synopsis'),
						primary_child: $('#primary_child'),
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
}

 $('#key_button').click(function() {
 	if($(this).hasClass('show_key')) {
 		$(this).removeClass().addClass('hide_key');
 		$('#key_button_wrap', this).html('Hide key')
 		$('#key').stop(true, true).slideDown();
 	} else {
 		$('#key_button_wrap', this).html('Show key');
 		$('#key').stop(true, true).slideUp('fast', function() {
 			$('#key_button').removeClass().addClass('show_key');
 		});
 	}
 });

 $('.data_refresh').click(function() {
 	location.reload(true);
 });