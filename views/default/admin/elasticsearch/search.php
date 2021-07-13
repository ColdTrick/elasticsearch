<?php

echo elgg_view_form('elasticsearch/admin_search', [
	'prevent_double_submit' => false,
]);

echo elgg_view_module('info', elgg_echo('elasticsearch:admin_search:results'), elgg_echo('elasticsearch:admin_search:results:info'), [
	'id' => 'elasticsearch-admin-search-results',
]);
