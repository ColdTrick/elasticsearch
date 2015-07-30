elgg.provide("elgg.elasticsearch");

elgg.elasticsearch.init_admin_search = function() {
	$('form.elgg-form-elasticsearch-admin-search').submit(function(event) {
		event.preventDefault();
		
		$form = $(this);
		
		require(['elgg/spinner'], function(spinner) {
			spinner.start();
			
			elgg.action('elasticsearch/admin_search', {
				data: $form.serialize(),
				complete: spinner.stop,
				success: function(data) {
					$('#elasticsearch-admin-search-results > .elgg-body').html(data.output);
				}
			});
		});
	});
};

//register init hook
elgg.register_hook_handler("init", "system", elgg.elasticsearch.init_admin_search);
