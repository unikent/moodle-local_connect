define(['jquery'], function($) {
	$("#id_module_delivery_key")
		.addClass('form-control')
			.wrap('<div class="input-group">')
		.parent()
			.append('<span class="input-group-btn"><button class="btn btn-default" type="button" id="mdksearch"><i class="fa fa-search"></i></button></span>');

	$("#mdksearch").on('click', function(e) {
		e.preventDefault();

		$('#searchmodal').modal();

		return false;
	});

	var searchTimeout;
	$("#searchmodalinput").on('keyup', function(e) {
		if (searchTimeout) {
			clearTimeout(searchTimeout);
		}

		searchTimeout = setTimeout(function(){
			var val = $("#searchmodalinput").val();
			if (val.length >= 5) {
				console.log(val);
			}
		}, 2000);
	});
});