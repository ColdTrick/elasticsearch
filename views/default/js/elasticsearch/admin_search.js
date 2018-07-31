define(function(require) {
	
	var $ = require('jquery');
	var Ajax = require('elgg/Ajax');
	
	$(document).on('submit', 'form.elgg-form-elasticsearch-admin-search', function(event) {
		event.preventDefault();
		
		var $form = $(this);
		var ajax = new Ajax();
		ajax.action('elasticsearch/admin_search', {
			data: ajax.objectify($form),
			success: function(data) {
				$('#elasticsearch-admin-search-results > .elgg-body').html(data);
			}
		});
	});
});
