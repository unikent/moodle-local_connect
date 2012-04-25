$(document).ready(function() {

	var push_timeout;
	var merge_timeout;
	var ui_timeout;

	var status = [];
	var statusHide = null;
	var selectedDeliveries = [];
	var delivery_list = '';
	var count = 0;
	var draws = 0;
	var complete_selected = [];
	var datatable;

	var ButtonLoader = (function() {

	  ButtonLoader.prototype.interval = null;

	  ButtonLoader.prototype.originalText = '';

	  ButtonLoader.prototype.disabledElement = null;

	  function ButtonLoader(element, loadingText) {
	    this.element = element;
	    this.loadingText = loadingText;
	    this.originalText = this.element.is('input') ? this.element.val() : this.element.html();
	  }

	  ButtonLoader.prototype.disable = function(element) {
	    this.disabledElement = element;
	    if (this.disabledElement.is('input') || this.disabledElement.is('button')) {
	      return this.disabledElement.attr('disabled', 'disabled');
	    }
	  };

	  ButtonLoader.prototype.start = function() {
	    var loading, tail,
	      _this = this;
	    this.updateText(this.loadingText);
	    tail = '.';
	    loading = function() {
	      if (tail.length > 3) tail = '';
	      _this.updateText(_this.loadingText + tail);
	      return tail += '.';
	    };
	    return this.interval = setInterval(loading, 350);
	  };

	  ButtonLoader.prototype.stop = function() {
	    clearInterval(this.interval);
	    this.updateText(this.originalText);
	    if (this.disabledElement) return this.disabledElement.removeAttr('disabled');
	  };

	  ButtonLoader.prototype.updateText = function(text) {
	    if (this.element.is('input')) {
	      return this.element.val(text);
	    } else {
	      return this.element.html(text);
	    }
	  };

	  return ButtonLoader;

	})();

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

	/*
	 * functions that outputs a message in the status box 
	 */
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

	function rowSelect(el) {
		var name = $('.status div', el).html().split('_').join(' ');

		//get the row checksum
		var ident = $(el).attr('ident');
		
		//application of styling for selected rows and error message handler for those that cannot be selected
		// also create an array of selected rows storing thier checksums
		if($('.status div', el).html() === 'unprocessed' || $('.status div', el).html() === 'failed in moodle') {
			if($(el).hasClass('row_selected')) {
				$(el).removeClass('row_selected');
				selectedDeliveries = _.reject(selectedDeliveries, function(num) { return num === ident; });
			} else {
				$(el).addClass('row_selected');
				selectedDeliveries.push($(el).attr('ident'));
			}
		} else if($('.status div', el).html() === 'created in moodle') {
			if($(el).hasClass('row_selected')) {
				$(el).removeClass('row_selected');
				selectedDeliveries = _.reject(selectedDeliveries, function(num) { return num === ident; });
				complete_selected = _.reject(complete_selected, function(num) { return num === ident; });

			} else {
				$(el).addClass('row_selected');
				selectedDeliveries.push($(el).attr('ident'));
				complete_selected.push($(el).attr('ident'));
			}
		} else {
			statusbox(el, 'Error: you cannot push a delivery with a status of ' + name);
		}

		
	}

	function rowSelectAll(els, sel_all) {
		$.each(els, function(el) {
			var ident = $(els[el]).attr('ident');
			if($('.status div', els[el]).html() === 'unprocessed' || $('.status div', els[el]).html() === 'failed in moodle') {
				if(sel_all === true && $(els[el]).hasClass('row_selected') === false) {
					$(els[el]).addClass('row_selected');
					selectedDeliveries.push($(els[el]).attr('ident'));
				} else if(sel_all === false) {
					$(els[el]).removeClass('row_selected');
					selectedDeliveries = _.reject(selectedDeliveries, function(num) { return num === ident; });
				}
			} else if($('.status div', els[el]).html() === 'created in moodle') {
				if(sel_all === true && $(els[el]).hasClass('row_selected') === false) {
					$(els[el]).addClass('row_selected');
					selectedDeliveries.push($(els[el]).attr('ident'));
					complete_selected.push($(els[el]).attr('ident'));
				} else if(sel_all === false) {
					$(els[el]).removeClass('row_selected');
					selectedDeliveries = _.reject(selectedDeliveries, function(num) { return num === ident; });
					complete_selected = _.reject(complete_selected, function(num) { return num === ident; });
				}
			}
		});
	}

	function processRowSelect() {
		//loops through the json data and finds the entries for the selected deliveries. Then grabs the short codes and appends it to a 
			//string as a list element.
			delivery_list = '';
			$(selectedDeliveries).each(function(index) {
				var	delivery = selectedDeliveries[index];
				var row = _.find(datatable, function (row) { 
					if(row[0] === delivery) {
						return row;
					}
				});
				delivery_list = delivery_list + '<li>' + row[2] + '</li>';
			});

			if($('#jobs ul').hasClass('visible')) {
				$('#jobs ul').html(delivery_list);
			}

			//gets the number of selected deliveries and appends it to the dom  
			count = selectedDeliveries.length;
			$('#job_number').html(count);

			if(selectedDeliveries.length > 1) {
				if(complete_selected.length > 1) {
					$('#push_deliveries').attr('disabled', 'disabled').html('Can\'t push').removeClass();
					$('#merge_deliveries').attr('disabled', 'disabled').html('Can\'t merge').removeClass();
				}else if(complete_selected.length === 1) {
					$('#push_deliveries').attr('disabled', 'disabled').html('Can\'t push').removeClass();
					$('#merge_deliveries').removeAttr('disabled').html('<span>Merge</span> to Moodle').removeClass();
				} else {
					$('#push_deliveries').removeAttr('disabled').html('<span>Push</span> to Moodle').removeClass();
					$('#merge_deliveries').removeAttr('disabled').html('<span>Merge</span> to Moodle').removeClass();
				}
			} else if(selectedDeliveries.length === 1) {
				if(complete_selected.length !== 0) {
					$('#push_deliveries').attr('disabled', 'disabled').html('Can\'t push').removeClass();
					$('#merge_deliveries').attr('disabled', 'disabled').html('Can\'t merge').removeClass();
				} else {
					$('#push_deliveries').removeAttr('disabled').html('<span>Edit</span> to Moodle').removeClass().addClass('edit_to_moodle');
					$('#merge_deliveries').attr('disabled', 'disabled').html('Can\'t merge').removeClass();
				}
			} else {
				$('#push_deliveries').attr('disabled', 'disabled').html('No selection').removeClass();
				$('#merge_deliveries').attr('disabled', 'disabled').html('No selection').removeClass();
			}	
	}

	function clear_ui_form() {
		$('#shortname').val('');
		$('#fullname').val('');
		$('#synopsis').val('');

		if($('#shortname_ext').get(0)) {
			$('#shortname_ext').val('');
		}
	}

	function edit_row(chksum, json, button){
		var row = _.filter(json, function (r) { 
				return r.chksum === chksum;
		});
		if(row[0].state[0] === 'unprocessed') {
			var row_unprocessed = true;
			var date = '(' + Date.parse(row[0].session_code).toString('yyyy');
			date += '/' + Date.parse(row[0].session_code).next().year().toString('yyyy') + ')';
			var synopsis = $.trim(row[0].synopsis).substring(0,500).split(" ").slice(0, -1).join(" ") + "...";

			var shortname = row[0].module_code + ' ' + date;
			var fullname = row[0].module_title + ' ' + date;
			
			if(_.find(json, function (r){ 
				if(r.state[0] === 'processing' || r.state[0] === 'scheduled' || r.state[0] === 'created_in_moodle') {
					return r.module_code === shortname;
				}
			}) !== undefined) {
				$('#shortname_ext_td').html('<input type="text" name="shortname_ext" id="shortname_ext" class="text ui-widget-content ui-corner-all" size="3" maxlength="3"/>');
				$('#edit_notifications').removeClass().addClass('warn').text('Shortname already in use. Please provide a three letter identifier');
				$('#shortname_ext').addClass('warn');
			}
		} else {
			var synopsis = row[0].synopsis;
			var shortname = row[0].module_code;
			var fullname = row[0].module_title;
		}

		var ui_sub;
		//Appends the data created to the form
		$('#shortname').val(shortname);
		$('#fullname').val(fullname);
		$('#synopsis').val(synopsis);
		$('#category').val(row[0].category_id);
		$( "#dialog-form" ).dialog({ 
			title: 'Choose details',
			close: function(event, ui) {
				if(row_unprocessed ===true) {
					button.stop();
					button.updateText('<span>Edit</span> to Moodle');
					$('#push_deliveries').removeClass().addClass('edit_to_moodle');
				}

				$('#shortname_ext_td').html('');
				$('#edit_notifications').html('');
				$('#edit_notifications').removeClass();
			},
			open: function(event, ui) {
				ui_sub = new ButtonLoader($('.ui-dialog-buttonpane').find('button:contains("Push to moodle")'), 'Saving');
			},
			buttons: {
				"Push to moodle": function() {
					
				 	ui_sub.disable($('.ui-dialog-buttonpane').find('button:contains("Push to moodle")'));
				 	ui_sub.start();

				 	if($('#shortname_ext').get(0)) {
				 		if($('#shortname_ext').val() === '') {
				 			$('#edit_notifications').removeClass('warn').addClass('error').text('Please provide a three letter identifier');
				 			$('#shortname_ext').addClass('error');
				 			ui_sub.stop();
				 			return;
				 		}
				 		shortname += ' ' + $('#shortname_ext').val();
				 	}

				 	if(row_unprocessed ===true) {
				 		synopsis = $('#synopsis').val() + " <a href='http://www.kent.ac.uk/courses/modulecatalogue/modules/"+ row[0].module_code +"'>More</a>"
				 	}

				 	var data = [{
				 		id: row[0].chksum,
				 		code: shortname,
				 		title: $('#fullname').val(),
				 		synopsis: synopsis,
				 		category: $('#category').val()
				 	}];

				 	push_selected(data, ui_sub, true, function() {

				 		clear_ui_form();
						$("#dialog-form").dialog( "close" );
						button.stop();
						button.updateText('Success');
						$('#push_deliveries').addClass('success');
						clearTimeout(push_timeout);
						push_timeout = setTimeout(function() {
							$(button.element[0]).removeClass();
							/*if(single === true) {
								button.updateText('<span class="ui-button-text">Push to Moodle<span>');
							} else {
								button.updateText('<span>Push</span> to Moodle');
							}*/
							processRowSelect();
						}, 3000);
				 	}, function(xhr) {

				 		var problems = JSON.parse(xhr.responseText);

				 		switch(problems[0].error_code) {
		 					case 'duplicate':
		 					case 'could_not_schedule':
		 						if($('#shortname_ext').get(0)) {
		 							$('#edit_notifications').addClass('error').text('Please provide a three letter identifier');
					 				$('#shortname_ext').addClass('error');
		 						} else {
		 							$('#shortname_ext_td').html('<input type="text" name="shortname_ext" id="shortname_ext" class="text ui-widget-content ui-corner-all" size="3" maxlength="3"/>');
									$('#edit_notifications').removeClass().addClass('warn').text('Shortname already in use. Please provide a three letter identifier');
									$('#shortname_ext').addClass('warn');
								}
		 					break;
		 				}

		 				clearTimeout(ui_timeout);
						ui_timeout = setTimeout(function() {
						$(ui_sub.element[0]).removeClass('error');
							ui_sub.updateText('<span class="ui-button-text">Push to Moodle<span>');
						}, 2000);
				 	});
			 												 	
				},
				Cancel: function() {
					clear_ui_form()
					$( this ).dialog( "close" );
				}
			}
		}).dialog("open" );
	}

	function push_selected(data, button, single, callback, errorcallback) {
		$.ajax({
	 		type: 'POST',
	 		url: window.dapageUrl + '/courses/schedule/',
	 		contentType: 'json',
	 		dataType: 'json',
	 		data: JSON.stringify({'courses': data }),
	 		success: function () {
				button.stop();
	 			$('#datable tbody tr').removeClass('row_selected');
	 			$('#push_deliveries').removeClass('loading');
	 			$(selectedDeliveries).each(function(index) {
	 				var row = $('#datable tbody tr[ident='+selectedDeliveries[index]+']');
	 				var aPos = oTable.fnGetPosition(row[0]);
	 				oTable.fnUpdate('<div class="status_scheduled">scheduled</div>', row[0], 1, false)
	 			})

	 			oTable.fnDraw();

	 			selectedDeliveries = [];
	 			count = 0;
	 			$('#job_number').html(count);
	 			delivery_list = '<li class="empty_deliv">no items have been selected</li>';
	 			if($('#jobs ul').hasClass('visible')) {
					$('#jobs ul').html(delivery_list);
				}
				
				
				button.updateText('Success');
				$('#push_deliveries').addClass('success');
				if(single === true) {
					$(button.element[0]).removeClass('loading');
					button.updateText('<span class="ui-button-text">Push to Moodle<span>');
				}

				callback();
	 		},
	 		error: function(xhr, request, settings) {
	 			button.stop();
	 			$(button.element[0]).removeClass('loading');
	 			button.updateText('Error');
				$(button.element[0]).addClass('error');

	 			errorcallback(xhr);

	 		}
	 	});
	}

	function fnFormatDetails (row) {
	    var sOut = '<table>';
	    	sOut += '<tr>';
	    	sOut += '<th>Code</th>';
	    	sOut += '<th>Name</th>';
	    	sOut += '<th>Campus</th>';
	    	sOut += '<th>Duration</th>';
	    	sOut += '<th>Students</th>';
	    	sOut += '<th>Version</th>';
	    	sOut += '<th></th>';
	    	sOut += '</tr>';
	    $.each(row.children, function(i) {
	    	var end = parseInt(row.children[i].module_week_beginning, 10) + parseInt(row.children[i].module_length, 10);
			var duration = row.children[i].module_week_beginning + '-' + end;
	    	sOut += '<tr ident="'+ row.children[i].chksum +'">';
	    	sOut += '<td class="code">'+ row.children[i].module_code +'</td>';
	    	sOut += '<td class="name">'+ row.children[i].module_title +'</td>';
	    	sOut += '<td class="campus">' + row.children[i].campus_desc +'</td>';
	    	sOut += '<td class="duration">'+ duration +'</td>';
	    	sOut += '<td class="students">'+ row.children[i].student_count +'</td>';
	    	sOut += '<td class="version">'+ row.children[i].module_version +'</td>';
	    	if(row.children.length > 1) {
	    		sOut += '<td class="toolbar"><div class="unlink_child"></div></td>';	
	    	} else {
	    		sOut += '<td class="toolbar"></td>';
	    	}
	    	
	    	sOut += '</tr>';
	    });
	    sOut += '</table>';
	     
	    return sOut;
	}

	$.fn.dataTableExt.oApi.fnGetFilteredNodes = function ( oSettings )
	{
	    var anRows = [];
	    for ( var i=0, iLen=oSettings.aiDisplay.length ; i<iLen ; i++ )
	    {
	        var nRow = oSettings.aoData[ oSettings.aiDisplay[i] ].nTr;
	        anRows.push( nRow );
	    }
	    return anRows;
	};

	/*
	 * Event handler for the clicking of a datatable element 
	*/
	function rowClick(json) {

		$('.child_expand').live('click', function() {
			var chksum = $(this).closest('tr').attr('ident');

			var row = _.filter(json, function (r) { 
				return r.chksum === chksum;
			});

			var nTr = $(this).parents('tr')[0];
			if(oTable.fnIsOpen(nTr)) {
				$(this).removeClass('close').addClass('open');
				oTable.fnClose(nTr);
			} else {
				$(this).removeClass('open').addClass('close');
				oTable.fnOpen( nTr, fnFormatDetails(row[0]), 'merged' );
			}
		});

		$('.unlink_row').live('click', function() {
			var chksum = $(this).closest('tr').attr('ident');
			var row = $(this).closest('tr');
			$(this).removeClass('unlink_row').addClass('ajax_loading');
			$.ajax({
		 		type: 'GET',
		 		url: window.dapageUrl + '/courses/bob',
				dataType: 'json',
		 		success: function () {

		 			if(oTable.fnIsOpen(row[0])) {
						row.removeClass('close').addClass('open');
						oTable.fnClose(row[0]);
					}

		 			row.removeClass('row_selected');
		 			var aPos = oTable.fnGetPosition(row[0]);
	 				oTable.fnUpdate('<div class="status_scheduled">scheduled</div>', row[0], 1, false)
	 				oTable.fnUpdate('', row[0], 8, false)

		 			oTable.fnDraw();

		 			selectedDeliveries = [];
		 			count = 0;
		 			$('#job_number').html(count);
		 			delivery_list = '<li class="empty_deliv">no items have been selected</li>';
		 			if($('#jobs ul').hasClass('visible')) {
						$('#jobs ul').html(delivery_list);
					}
		 		},
		 		error: function() {
		 			$('.ajax_loading', row).removeClass('ajax_loading').addClass('unlink_row');
		 			statusbox(row, 'Error: we were unable to process your request at this time. Please try later');
		 		}
		 	});
		});

		$('.unlink_child').live('click', function() {
			var chksum = $(this).closest('tr').attr('ident');
			var row = $(this).closest('tr');
			var children =$(row).closest('.merged tbody');

			$(this).removeClass('unlink_child').addClass('ajax_loading');

			$.ajax({
		 		type: 'GET',
		 		url: window.dapageUrl + '/courses/',
				dataType: 'json',
		 		success: function () {

		 			console.log($(children).find('tr[ident='+chksum+'] .code').text());
	 				var data = [
		 				chksum,
		 				'<div class="status_scheduled">scheduled</div>',
		 				$(children).find('tr[ident='+chksum+'] .code').text(),
		 				$(children).find('tr[ident='+chksum+'] .name').text(),
		 				$(children).find('tr[ident='+chksum+'] .campus').text(),
		 				$(children).find('tr[ident='+chksum+'] .duration').text(),
		 				$(children).find('tr[ident='+chksum+'] .students').text(),
		 				$(children).find('tr[ident='+chksum+'] .version').text(),
		 				' '
		 			];
	 				oTable.fnAddData(data);
	 				$(children).find('tr[ident='+chksum+']').remove();
	 				var count = $('tr', children).length;

	 				if(count === 2) {
	 					$(children).find('.unlink_child').remove();
	 				}
		 		},
		 		error: function() {
		 			$('.ajax_loading', row).removeClass('ajax_loading').addClass('unlink_child');
		 			statusbox(row, 'Error: we were unable to process your request at this time. Please try later');
		 		}
		 	});
		});

		$('.edit_row').live('click', function() {
			var chksum = $(this).closest('tr').attr('ident');

			edit_row(chksum, json, null);
		});

		$('#datable tbody tr.parent').live('click', function() {

			clearTimeout(push_timeout);
			clearTimeout(ui_timeout);
			clearTimeout(merge_timeout);
			if(event.target === $('.toolbar a',this)[0] || event.target === $('.toolbar div',this)[0]){
				return true;
			}
			rowSelect(this, false);

			processRowSelect();
		});
	};

	function startConnect(json) {
		//First check if we have courses and error if so.
        if (json.length === 0){
            jQuery.unblockUI();
            $('#dialog_error').html('You do not have access to any deliveries at this time.');
            $("#dialog_error").dialog("open");
            return
        }

		//taking json and mapping into usable data
		datatable = _.map(json, function(val) {
			var end = parseInt(val.module_week_beginning, 10) + parseInt(val.module_length, 10);
			var duration = val.module_week_beginning + '-' + end;
			var name = val.state[0].split('_').join(' ');
			var state = '<div class="status_'+val.state[0]+'">'+name+'</div>';
			var toolbar = ' ';
			if(val.state[0] === 'created_in_moodle') {
				if(val.children !== null) {
					toolbar += '<div class="child_expand open toolbar_link"></div>'
				}
				toolbar += '<div class="unlink_row toolbar_link"></div>'
				toolbar += '<div class="edit_row toolbar_link"></div>'
				toolbar += '<a href=" '+ window.coursepageUrl + '/course/view.php?id='+ val.moodle_id +'" target="_blank" class="created_link toolbar_link"></a>';
				
			}
			
			return [val.chksum, state, val.module_code, val.module_title, val.campus_desc, duration, val.student_count, val.module_version, toolbar];

		});

		// prepending status box to the dom and hiding it ready for use
		$('#datable_wrapper').prepend('<div id="statusbox"></div>');
		$('#statusbox').hide();

		$('#select_all').click(function() {
			var rows = oTable.fnGetFilteredNodes();
			rowSelectAll(rows, true);
			processRowSelect();
			
		});

		$('#deselect_all').click(function() {
			var rows = oTable.fnGetNodes();
			rowSelectAll(rows, false);
			processRowSelect();
		});

		var textareas = document.getElementsByTagName('textarea');
		for (var i = textareas.length; i--;) {
		    if (textareas[i].getAttribute('maxlength') && !textareas[i].maxlength) {
		        var max = textareas[i].getAttribute('maxlength');
		        textareas[i].onkeypress = function(event) {
		            var k = event ? event.which : window.event.keyCode;
		            if(this.value.length >= max) if(k>46 && k<112 || k>123) return false;
		        }
		    }
		}

		var oTable = $('#datable').dataTable( {
			"bProcessing": true,
			"aaData": datatable,
			"bAutoWidth": false,
			"aoColumns": [
				{'sClass': 'id', "bSearchable": false},
				{'sClass': 'status'},
				{'sClass': 'code'},
				{'sClass': 'name', "sWidth": "20%"},
				{'sClass': 'campus', "sWidth": "20%"},
				{'sClass': 'duration'},
				{'sClass': 'students'},
				{'sClass': 'version'},
				{'sClass': 'toolbar'}

			],
			"aoColumnDefs": [
				{ "bSearchable": true, "bVisible": false, "aTargets": [ 0 ] },
			],
			"oLanguage": {
				"sSearch": "Search all columns:"
			},
			"iDisplayLength": 50,
			"bPaginate" : true,
			"sPaginationType": "full_numbers",
			"fnCreatedRow": function( nRow, aData, iDataIndex ) {
				$(nRow).attr('ident', aData[0]);
				$(nRow).addClass('parent')
			},
			"fnInitComplete": function(oSettings) {
				jQuery.unblockUI();
				rowClick(json);	
			}
		});

		$('#datable_wrapper').prepend('<div id="statusbox"></di>');
		$('#statusbox').hide();

		$('#datable th').click(function() {
			$('#statusbox').fadeOut('fast');
		});

		/*
		 * Controls the hide and show list of short codes 
		*/
		$('#display_list_toggle').click(function() {
			$(this).toggleClass('display_list_open');
			if($('#jobs ul').hasClass('visible')) {
				$('#jobs ul').slideUp('fast', function() {
					$(this).html('').removeClass('visible');
				});
				$('div', this).show();
				$('button', this).html('show deliveries');
			} else {
				$('div', this).hide();
				$('button', this).html('hide deliveries');
				if(delivery_list === '') {
					delivery_list = '<li class="empty_deliv">no items have been selected</li>';
				}
				$('#jobs ul').html(delivery_list).hide().slideDown('fast').addClass('visible');
			}
		});


		status = [ 'unprocessed', 'created_in_moodle', 'failed_in_moodle', 'scheduled', 'processing' ];
		oTable.columnFilter({
			aoColumns: [
				null,
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
		
		$('#dasearch input').keyup(function() {
			if($(this).val().length > 1) {
				oTable.fnFilter($(this).val());
			} else {
				oTable.fnFilter('')
			}
		});

		$('#unprocessed').attr('checked', 'checked');
		$('#status_unprocessed').attr('checked','checked').trigger('change');

		$('.status_checkbox').change(function() {
			$('#statusbox').hide();
			if($(this).is(':checked')) {
				var val = $(this).val();
				$('#status_' + val).attr('checked','checked').trigger('change');
			} else {
				var val = $(this).val();
				$('#status_' + val).removeAttr('checked').trigger('change');
			}
		});

		/*
		 * function that deals with the submitting of delivery jobs
		 */
		$('#push_deliveries').click(function() {

			var button = new ButtonLoader($(this), 'Saving');
		 	$(this).attr('disabled', 'disabled').addClass('loading');
		 	button.start();
		 	button.disable($(this));

			if($('#push_deliveries').hasClass('edit_to_moodle')) {

				edit_row(selectedDeliveries[0], json, button);

			} else {

				var pushees = _.filter(json, function (row) { 
					if(_.indexOf(selectedDeliveries, row.chksum) !== -1){
						return row;
					}
				});

				var date = '(' + Date.parse(pushees[0].session_code).toString('yyyy');
				date += '/' + Date.parse(pushees[0].session_code).next().year().toString('yyyy') + ')';

				var data = [];

				$.each(pushees, function(i) {

					var synopsis = $.trim(pushees[i].synopsis).substring(0,500).split(" ").slice(0, -1).join(" ") + "...";

					var obj = {
						id: pushees[i].chksum,
						code: pushees[i].module_code + ' ' + date,
						title: pushees[i].module_title + ' ' + date,
						synopsis: synopsis + '  <a href="http://www.kent.ac.uk/courses/modulecatalogue/modules/'+ pushees[i].module_code +'">More</a>',
						category: '1'
					}

					data.push(obj);
				});

				var status = push_selected(data, button, false, function(){
					clearTimeout(push_timeout);
					push_timeout = setTimeout(function() {
						$(button.element[0]).removeClass();
						/*if(single === true) {
							button.updateText('<span class="ui-button-text">Push to Moodle<span>');
						} else {
							button.updateText('<span>Push</span> to Moodle');
						}*/
						processRowSelect();
					}, 3000); 
				}, function(xhr) {
					var problems = JSON.parse(xhr.responseText);
		 			var error_ids = [];
		 			var errors = '<div id="push_notifications" class="warn">Note: courses that have not errored have still been scheduled.</div>';
		 			errors += '<ul id="error_ui">';

		 			$.each(problems, function(i) {
		 				
		 				var row = _.filter(data, function (r) { 
							return r.id === problems[i].id;
						});

		 				error_ids.push(row[0].id);

		 				switch(problems[i].error_code) {
		 					case 'duplicate':
		 						errors += '<li class="warning"><span class="type">WARNING: Duplicate</span> - <span class="cours_dets">';
		 						errors += row[0].code + ': ' + row[0].title + '</span>. Please merge or push through individually';
		 						errors += '</li>';
		 					break;
		 					case 'could_not_schedule':
		 						errors += '<li class="error"><span class="type">ERROR: Already scheduled</span> - <span class="cours_dets">';
		 						errors += row[0].code + ': ' + row[0].title + '</span>. Please merge or push through individually';
		 						errors += '</li>';
		 					break;
		 					case 'category_is_zero':
		 						errors += '<li class="error"><span class="type">ERROR: Category not found</span> - <span class="cours_dets">';
		 						errors += row[0].code + ': ' + row[0].title + '</span>. Push through individually to choose a relevant category';
		 						errors += '</li>';
		 					break;
		 				}
		 			});

		 			errors += '</ul>';
									 			
					$('#dialog_error').html(errors);
					$("#dialog_error").dialog({
						width: 500,
						height: 400
					}).dialog("open");
					//statusbox($('#datable tbody tr:first'), 'Duplicates detected! To resolve please merge or push through individually');
					selectedDeliveries = _.without(selectedDeliveries, error_ids);
					$('#datable tbody tr').removeClass('row_selected');
		 			$(selectedDeliveries).each(function(i) {
		 				var row = $('#datable tbody tr[ident='+selectedDeliveries[i]+']');
		 				var aPos = oTable.fnGetPosition(row[0]);
		 				oTable.fnUpdate('<div class="status_scheduled">scheduled</div>', row[0], 1, false)
		 			});

		 			oTable.fnDraw();

		 			selectedDeliveries = [];
		 			count = 0;
		 			$('#job_number').html(count);
		 			delivery_list = '<li class="empty_deliv">no items have been selected</li>';
		 			if($('#jobs ul').hasClass('visible')) {
						$('#jobs ul').html(delivery_list);
					}

					$(error_ids).each(function(i) {
		 				var dom_row = $('#datable tbody tr[ident='+error_ids[i]+']')[0];
		 				rowSelect(dom_row, false);

		 			});

					clearTimeout(push_timeout);
					push_timeout = setTimeout(function() {
						$(button.element[0]).removeClass();
						/*if(single === true) {
							button.updateText('<span class="ui-button-text">Push to Moodle<span>');
						} else {
							button.updateText('<span>Push</span> to Moodle');
						}*/
						processRowSelect();
					}, 2000);
				});
				
			}
		});

		/*
		 * function that deals with the merging of delivery jobs
		 */
		$('#merge_deliveries').click(function() {

			// Set the button appearence and functionality
			var button = new ButtonLoader($(this), 'Saving');
		 	$(this).attr('disabled', 'disabled').addClass('loading');
		 	button.start();
		 	button.disable($(this));

		 	//Gets the data objects for all of the selected rows
			var mergers = _.chain(json).filter(function (row) { 
				if(_.indexOf(selectedDeliveries, row.chksum) !== -1){
					return row;
				}
			}).sortBy(function(row) {
				return [row.module_week_beginning, row.campus_desc, row.module_code];
			}).value();

			//Setting up our vars which control data to be sent and appears in form
			var short_name  = '';
			var mod_code = '';
			var full_name = '';
			var synopsis = '';
			mergers_count = 0;

			//Finds the first synopsis in the selected deliveries and uses this
			while(synopsis === '') {
				if(mergers_count > mergers.length-1) {
					break;
				} else {
					synopsis = mergers[mergers_count].synopsis;
					var mod_code = mergers[mergers_count].module_code;
					mergers_count ++;
				}
			}

			//Limits the synop length and adds a ... on the end
			synopsis = $.trim(synopsis).substring(0,500).split(" ").slice(0, -1).join(" ") + "...";

			//Creates the combined shorts and long names
			$.each(mergers, function(i, val) {
				var code = val.module_code.split(' ');
				if(code.length > 1) {
					short_name += code[0];
				} else {
					short_name += val.module_code;	
				}

				full_name += val.module_title.replace(/( )(\()(\d+)(\/)(\d+)(\))/i, '');
			
				//full_name += val.module_title;
				if(i !== mergers.length-1) {
					short_name += '/';
					full_name += '/';
				}
			});

			//Gets the year from the first delivery and creates the date string from this.
			//Then appends this to the short and full name
			var date = '(' + Date.parse(mergers[0].session_code).toString('yyyy');
			date += '/' + Date.parse(mergers[0].session_code).next().year().toString('yyyy') + ')';
			short_name += ' ' + date;
			full_name += ' ' + date;

			//Appends the data created to the form
			$('#shortname').val(short_name);
			$('#fullname').val(full_name);
			$('#synopsis').val(synopsis);
			$('#category').val(mergers[0].category_id);

			var ui_sub;

			$( "#dialog-form" ).dialog({ 
				title: 'Choose merge details',
				close: function(event, ui) {
					button.stop();
					button.updateText('<span>Merge</span> to Moodle');
					$('#merge_deliveries').removeClass();

					$('#shortname_ext_td').html('');
					$('#edit_notifications').html('');
					$('#edit_notifications').removeClass();
				},
				open: function(event, ui) {
					ui_sub = new ButtonLoader($('.ui-dialog-buttonpane').find('button:contains("Push to moodle")'), 'Saving');
				},
				buttons: {
					"Push to moodle": function() {
					 	ui_sub.disable($('.ui-dialog-buttonpane').find('button:contains("Push to moodle")'));
					 	ui_sub.start();

					 	if($('#shortname_ext').get(0)) {
					 		if($('#shortname_ext').val() === '') {
					 			$('#edit_notifications').removeClass('warn').addClass('error').text('Please provide a three letter identifier');
					 			$('#shortname_ext').addClass('error');
					 			ui_sub.stop();
					 			return;
					 		}

					 		short_name += ' ' + $('#shortname_ext').val();
					 	}

					 	var data = {
					 		link_courses: selectedDeliveries,
					 		code: short_name,
					 		title: full_name,
					 		synopsis: synopsis + " <a href='http://www.kent.ac.uk/courses/modulecatalogue/modules/"+ mod_code +"'>More</a>",
					 		category: $('#category').val()
					 	};

					 	$.ajax({
					 		type: 'POST',
					 		url: window.dapageUrl + '/courses/merge/',
					 		contentType: 'json',
		 					dataType: 'json',
					 		data: JSON.stringify(data),
					 		success: function () {
					 			ui_sub.stop();
					 			$('#datable tbody tr').removeClass('row_selected');
					 			$('#merge_deliveries').removeClass('loading');
					 			$(selectedDeliveries).each(function(index) {
					 				var row = $('#datable tbody tr[ident='+selectedDeliveries[index]+']');
					 				var aPos = oTable.fnGetPosition(row[0]);
					 				oTable.fnUpdate('<div class="status_scheduled">scheduled</div>', row[0], 1, false);
					 			})

					 			oTable.fnDraw();

					 			selectedDeliveries = [];
					 			count = 0;
					 			$('#job_number').html(count);
					 			delivery_list = '<li class="empty_deliv">no items have been selected</li>';
					 			if($('#jobs ul').hasClass('visible')) {
									$('#jobs ul').html(delivery_list);
								}

								clear_ui_form()
								$("#dialog-form").dialog("close");

								button.stop();
								button.updateText('Success');
								$('#merge_deliveries').addClass('success');
								clearTimeout(merge_timeout);
								merge_timeout = setTimeout(function() {
									$('#merge_deliveries').removeClass('success').attr('disabled', 'disabled');
									processRowSelect();
								}, 4000);


					 		},
					 		error: function(xhr, request, settings) {


					 			var problems = JSON.parse(xhr.responseText);

						 		switch(problems[0].error_code) {
				 					case 'duplicate':
				 					case 'could_not_schedule':
				 						if($('#shortname_ext').get(0)) {
				 							$('#edit_notifications').addClass('error').text('Please provide a three letter identifier');
							 				$('#shortname_ext').addClass('error');
				 						} else {
				 							$('#shortname_ext_td').html('<input type="text" name="shortname_ext" id="shortname_ext" class="text ui-widget-content ui-corner-all" size="3" maxlength="3"/>');
											$('#edit_notifications').removeClass().addClass('warn').text('Shortname already in use. Please provide a three letter identifier');
											$('#shortname_ext').addClass('warn');
										}
				 					break;
				 				} 

					 			ui.stop();
					 			$(ui.element[0]).removeClass('loading');
					 			ui.updateText('Error');
								$(ui.element[0]).addClass('error');
								clearTimeout(merge_timeout);
								merge_timeout = setTimeout(function() {
									ui_sub.updateText('<span class="ui-button-text">Push to Moodle<span>');
									$('#merge_deliveries').removeClass('error');
								}, 4000);
					 		}
					 	});								 										 	
					},
					Cancel: function() {
						clear_ui_form()
						$( this ).dialog( "close" );
					}
				}
			}).dialog("open" );
		});
	}


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
			startConnect(json);
		},
		error: function(event) {
			console.log('lol');
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
	
}); //doc.read end
