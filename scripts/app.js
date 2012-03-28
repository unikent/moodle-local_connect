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

					var end = parseInt(val.module_week_beginning) + parseInt(val.module_length);
					var duration = val.module_week_beginning + '-' + end;
					var name = val.state[0].split('_').join(' ');
					var state = '<div class="status_'+val.state[0]+'">'+name+'</div>';

					return [val.chksum, state, val.module_code, val.module_title, val.campus, duration];

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
/*
				// Setting up datatables natural sort asc using the natural sort plugin
				jQuery.fn.dataTableExt.oSort['natural-asc']  = function(a,b) {
	    		return naturalSort(a,b);
				};
	 			
	 			// Setting up datatables natural sort desc using the natural sort plugin
				jQuery.fn.dataTableExt.oSort['natural-desc'] = function(a,b) {
				    return naturalSort(a,b) * -1;
				};
*/
				var status = [];
				var statusHide = null;
				var selectedDeliveries = [];
				var delivery_list = '';
				var count = 0;

				var oTable = $('#datable').dataTable( {
					"bProcessing": true,
					"aaData": datatable,
					"aoColumns": [
/*						{'sClass': 'id', 'sType': 'natural'},
						{'sClass': 'status', 'sType': 'natural'},
						{'sClass': 'code', 'sType': 'natural'},
						{'sClass': 'name', 'sType': 'natural'},
						{'sClass': 'campus', 'sType': 'natural'},
						{'sClass': 'duration', 'sType': 'natural'}, */
						{'sClass': 'id' },
						{'sClass': 'status'},
						{'sClass': 'code'},
						{'sClass': 'name'},
						{'sClass': 'campus'},
						{'sClass': 'duration'}


					],
					"aoColumnDefs": [
						{ "bSearchable": true, "bVisible": false, "aTargets": [ 0 ] },
					],
					"oLanguage": {
						"sSearch": "Search all columns:"
					},
					"bPaginate" : true,
					"fnCreatedRow": function( nRow, aData, iDataIndex ) {
						$(nRow).attr('ident', aData[0]);
					},
					"fnInitComplete": function(oSettings, json) {
						jQuery.unblockUI();
					}
				});

				$('#datable_wrapper').prepend('<div id="statusbox"></di>');
				$('#statusbox').hide();

				$('#datable th').click(function() {
					$('#statusbox').fadeOut('fast');
				});

				/*
				 * Event handler for the clicking of a datatable element 
				*/
				$('#datable tbody tr').click(function() {

					//create a nicer display version of the status name
					var name = $('.status div', this).html().split('_').join(' ');

					//get the row checksum
					var ident = $(this).attr('ident');

					//application of styling for selected rows and error message handler for those that cannot be selected
					// also create an array of selected rows storing thier checksums
					if($('.status div', this).html() === 'unprocessed' || $('.status div', this).html() === 'failed in moodle') {
						if($(this).hasClass('row_selected')) {
							$(this).removeClass('row_selected');
							selectedDeliveries = _.reject(selectedDeliveries, function(num) { return num === ident; });
						} else {
							$(this).addClass('row_selected');
							selectedDeliveries.push($(this).attr('ident'));
						}
					} else {
						statusbox(this, 'Error: you cannot push a delivery with a status of ' + name);
					}

					//gets the number of selected deliveries and appends it to the dom  
					count = selectedDeliveries.length;
					$('#job_number').html(count);
					
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

					if(selectedDeliveries.length !== 0) {
						$('#push_deliveries').removeAttr('disabled').html('<span>Push</span> to Moodle').removeClass();
					} else {
						$('#push_deliveries').attr('disabled', 'disabled').html('No selection').removeClass();
					}
						
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


				//status = _.uniq(_.map(datatable, function(val) { return $(val[1]).html().split(' ').join('_');; }));
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

				/*$(status).each(function(val) {
					var spaces = [];
					spaces[val] = status[val].split('_').join(' ');
					$('<li><input type="checkbox" name="'+status[val]+'" value="'+status[val]+'"  id="'+status[val]+'" class="status_checkbox"/><label id="label-'+status[val]+'" for="'+status[val]+'">'+spaces[val]+'</label></li>').appendTo('#status_toggle');
				});*/
				
				$('#dasearch input').keyup(function() {
						oTable.fnFilter($(this).val());
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
				 	})
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
