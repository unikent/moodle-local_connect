$(document).ready(function() {

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
		height: 490,
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

	$.ajax({
		url: window.dapageUrl + '/courses/',
		dataType: 'json',
		success: function(json) {
			//First check if we have courses and error if so.
	        if (json === null){
	            jQuery.unblockUI();
	            $("#dialog_error").dialog("open");
	        } else {

				//taking json and mapping into usable data
				var datatable = _.map(json, function(val) {
					var end = parseInt(val.module_week_beginning, 10) + parseInt(val.module_length, 10);
					var duration = val.module_week_beginning + '-' + end;
					var name = val.state[0].split('_').join(' ');
					var state = '<div class="status_'+val.state[0]+'">'+name+'</div>';
					var toolbar = ' ';
					if(val.state[0] === 'created_in_moodle') {
						toolbar += '<div class="edit_row toolbar_link"></div>'
						toolbar += '<a href=" '+ window.coursepageUrl + '/course/view.php?id='+ val.moodle_id +'" target="_blank" class="created_link toolbar_link"></a>';
					}
					return [val.chksum, state, val.module_code, val.module_title, val.campus_desc, duration, val.student_count, val.module_version, toolbar];

				});

				// prepending status box to the dom and hiding it ready for use
				$('#datable_wrapper').prepend('<div id="statusbox"></div>');
				$('#statusbox').hide();


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
							if(complete_selected.length !== 0) {
								$('#push_deliveries').attr('disabled', 'disabled').html('Cant push').removeClass();
							} else {
								$('#push_deliveries').removeAttr('disabled').html('<span>Push</span> to Moodle').removeClass();
							}
							$('#merge_deliveries').removeAttr('disabled').html('<span>Merge</span> to Moodle').removeClass();
						} else if(selectedDeliveries.length === 1) {
							if(complete_selected.length !== 0) {
								$('#push_deliveries').attr('disabled', 'disabled').html('Cant push').removeClass();
							} else {
								$('#push_deliveries').removeAttr('disabled').html('<span>Edit</span> to Moodle').removeClass().addClass('edit_to_moodle');
								$('#merge_deliveries').attr('disabled', 'disabled').html('No selection').removeClass();
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

					//Appends the data created to the form
					$('#shortname').val(shortname);
					$('#fullname').val(fullname);
					$('#synopsis').val(synopsis);
					
					$( "#dialog-form" ).dialog({ 
									title: 'Choose details',
									close: function(event, ui) {
										if(row_unprocessed ===true) {
											button.stop();
											button.updateText('<span>Edit</span> to Moodle');
											$('#push_deliveries').removeClass();
										}
									},
									buttons: {
										"Push to moodle": function() {
											var ui_sub = new ButtonLoader($('.ui-dialog-buttonpane').find('button:contains("Push to moodle")'), 'Saving');
										 	ui_sub.disable($('.ui-dialog-buttonpane').find('button:contains("Push to moodle")'));
										 	ui_sub.start();

										 	if($('#shortname_ext').get(0)) {
										 		if($('#shortname_ext').val() === '') {
										 			$('#edit_notifications').addClass('error').text('Please provide a three letter identifier');
										 			$('#shortname_ext').addClass('error');
										 			ui_sub.stop();
										 			return;
										 		}

										 		var short_name_val = short_name + ' ' + $('#shortname_ext').val();

										 		if(_.find(json, function (row){ return row.module_code === short_name_val}) !== undefined) {
										 			$('#edit_notifications').addClass('error').text('This combination is already in use please pick another');
										 			$('#shortname_ext').addClass('error');
										 			ui_sub.stop();
										 			return;
										 		} 
										 		short_name += ' ' + $('#shortname_ext').val();
										 	}

										 	var data = {
										 		chksum: pushees[0].chksum,
										 		code: short_name,
										 		title: full_name,
										 		synopsis: synopsis + " <a href='http://www.kent.ac.uk/courses/modulecatalogue/modules/"+ mod_code +"'>More</a>",
										 		category: $('#category').val()
										 	};

										 	/*TODO: ajax post functionality */

										 	clear_ui_form()
											$( this ).dialog( "close" );									 										 	
										},
										Cancel: function() {
											clear_ui_form()
											$( this ).dialog( "close" );
										}
									}
								}).dialog("open" );
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

				var status = [];
				var statusHide = null;
				var selectedDeliveries = [];
				var delivery_list = '';
				var count = 0;
				var draws = 0;
				var complete_selected = [];
				/*
				 * Event handler for the clicking of a datatable element 
				*/
				function rowClick(json) {

					$('.edit_row').live('click', function() {
						chksum = $(this).closest('tr').attr('ident');

						edit_row(chksum, json, null);
					});

					$('#datable tbody tr').live('click', function() {
						if(event.target === $('.toolbar a',this)[0] || event.target === $('.toolbar div',this)[0]){
							return true;
						}
						rowSelect(this, false);

						processRowSelect();
					});
				};

				$('#select_all').click(function() {
					var rows = oTable.fnGetFilteredNodes();
					rowSelectAll(rows, true);
					processRowSelect();
					
				});

				$('#deselect_all').click(function() {
					var rows = oTable.fnGetFilteredNodes();
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
					"aoColumns": [
						{'sClass': 'id', "bSearchable": false},
						{'sClass': 'status'},
						{'sClass': 'code'},
						{'sClass': 'name'},
						{'sClass': 'campus'},
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

					$.ajax({
						url: window.dapageUrl + '/courses/',
						dataType: 'json',
						success: function(valid_data) {
							if($('#push_deliveries').hasClass('edit_to_moodle')) {
								edit_row(selectedDeliveries[0], valid_data, button);
							} else {

								var pushees = _.filter(json, function (row) { 
									if(_.indexOf(selectedDeliveries, row.chksum) !== -1){
										return row;
									}
								});

								var date = '(' + Date.parse(pushees[0].session_code).toString('yyyy');
								date += '/' + Date.parse(pushees[0].session_code).next().year().toString('yyyy') + ')';

								var duplicates = _.chain(pushees).groupBy(function(item) {
									return item.module_code;
								}).reject(function(item) {
									return item.length < 2;
								}).flatten().map(function(item) {
									return item;
								}).value();

								$.each(pushees, function(i){
									var shortname = pushees[i].module_code + ' ' + date;
									if(_.find(valid_data, function (row){ 
										if(row.state[0] === 'processing' || row.state[0] === 'scheduled' || row.state[0] === 'created_in_moodle') {
											return row.module_code === shortname;
										}
										}) !== undefined) {
											duplicates.push(pushees[i]);
										}
									});

								if(duplicates.length !== 0) {
									$('#dialog_error').html('Duplicates detected! To resolve please merge or push through individually. If you have other selections they will now be pushed through');
									$("#dialog_error").dialog("open");
									//statusbox($('#datable tbody tr:first'), 'Duplicates detected! To resolve please merge or push through individually');
									pushees = _.without(pushees, duplicates);

									
								}

								var data = [];

								$.each(pushees, function(i) {

									var synopsis = $.trim(pushees[i].synopsis).substring(0,500).split(" ").slice(0, -1).join(" ") + "...";

									var obj = {
										chksum: pushees[i].chksum,
										module_code: pushees[i].module_code + ' ' + date,
										module_title: pushees[i].module_title + ' ' + date,
										synopsis: synopsis + '  <a href="http://www.kent.ac.uk/courses/modulecatalogue/modules/'+ pushees[i].module_code +'">More</a>',
										category: '1'
									}

									data.push(obj);
								});
								/*TODO: ajax post functionality*/
								$.ajax({
							 		type: 'POST',
							 		url: window.dapageUrl + '/courses/schedule/',
							 		data: {'ids':selectedDeliveries},
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

										setTimeout(function() {
											button.updateText('No selection');
											$('#push_deliveries').removeClass('success').attr('disabled', 'disabled');
										}, 4000);
							 		},
							 		error: function() {
							 			button.stop();
							 			$('#push_deliveries').removeClass('loading');
							 			button.updateText('Error');
										$('#push_deliveries').addClass('error');

										setTimeout(function() {
											button.updateText('<span>Push</span> to Moodle');
											$('#push_deliveries').removeClass('error');
										}, 4000);
							 		}
							 	});
								/*button.stop();
								button.updateText('<span>Push</span> to Moodle');
								$('#push_deliveries').removeClass();*/
							}

						},
						error: function() {
							button.stop();
				 			$('#push_deliveries').removeClass('loading');
				 			button.updateText('Error');
							$('#push_deliveries').addClass('error');

							setTimeout(function() {
								$('#push_deliveries').removeClass('error');
								processRowSelect();
							}, 4000);
						}
					});
			 	
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
						short_name +=val.module_code;
						full_name += val.module_title;
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

					//Ajax request to get the latest list of data
					$.ajax({
						url: window.dapageUrl + '/courses/',
						dataType: 'json',
						success: function(json) {

							//Checks to see if the shortname is already an active delivery
							//If it is then is adds a form element to make it unique
							if(_.find(json, function (row){ 
								if(row.state[0] === 'processing' || row.state[0] === 'scheduled' || row.state[0] === 'created_in_moodle') {
									return row.module_code === shortname;
								}
							}) !== undefined) {
								$('#shortname_ext_td').html('<input type="text" name="shortname_ext" id="shortname_ext" class="text ui-widget-content ui-corner-all" size="3" maxlength="3"/>');
								$('#edit_notifications').removeClass().addClass('warn').text('Shortname already in use. Please provide a three letter identifier');
								$('#shortname_ext').addClass('warn');
							}

							$( "#dialog-form" ).dialog({ 
								title: 'Choose merge details',
								close: function(event, ui) {
									button.stop();
									button.updateText('<span>Merge</span> to Moodle');
									$('#merge_deliveries').removeClass();
								},
								buttons: {
									"Push to moodle": function() {
										var ui_sub = new ButtonLoader($('.ui-dialog-buttonpane').find('button:contains("Push to moodle")'), 'Saving');
									 	ui_sub.disable($('.ui-dialog-buttonpane').find('button:contains("Push to moodle")'));
									 	ui_sub.start();

									 	if($('#shortname_ext').get(0)) {
									 		if($('#shortname_ext').val() === '') {
									 			$('#edit_notifications').addClass('error').text('Please provide a three letter identifier');
									 			$('#shortname_ext').addClass('error');
									 			ui_sub.stop();
									 			return;
									 		}

									 		var short_name_val = short_name + ' ' + $('#shortname_ext').val();

									 		if(_.find(json, function (row){ return row.module_code === short_name_val}) !== undefined) {
									 			$('#edit_notifications').addClass('error').text('This combination is already in use please pick another');
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
									 		data: data,
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

												button.updateText('Success');
												$('#merge_deliveries').addClass('success');

												setTimeout(function() {
													button.updateText('No selection');
													$('#merge_deliveries').removeClass('success').attr('disabled', 'disabled');
												}, 4000);

									 		},
									 		error: function() {
									 			button.stop();
									 			$('#merge_deliveries').removeClass('loading');
									 			button.updateText('Error');
												$('#merge_deliveries').addClass('error');

												setTimeout(function() {
													button.updateText('<span>Merge</span> to Moodle');
													$('#merge_deliveries').removeClass('error');
												}, 4000);
									 		}
									 	});

									 	clear_ui_form()

										$( this ).dialog( "close" );									 										 	
									},
									Cancel: function() {
										clear_ui_form()
										$( this ).dialog( "close" );
									}
								}
							}).dialog("open" );
						},
						error: function(){
							button.stop();
							jQuery.unblockUI();
						    $("#dialog_error").dialog("open");

						    $('#merge_deliveries').addClass('error');

						    setTimeout(function() {
									button.updateText('<span>Merge</span> to Moodle');
									$('#push_deliveries').removeClass('error');
								}, 4000);
						}
					});	
				});

			}
		}, // end of ajax success
		error: function() {
			jQuery.unblockUI();
	        $("#dialog_error").dialog("open");
		}
	}); // end of ajax call

	var $scrolldiv = $('#jobs_wrapper');

	$(window).scroll(function() {
		$scrolldiv.stop().css({
			'marginTop': ($(window).scrollTop()) + 'px'

		}, 'fast');
	})
	
}); //doc.read end
