<?php

use ColdTrick\ElasticSearch\Di\IndexManagementService;

$index_client = IndexManagementService::instance();
if (!$index_client->isClientReady()) {
	echo elgg_echo('elasticsearch:error:no_client');
	return;
}

elgg_require_js('forms/elasticsearch/admin_search');

echo elgg_view_field([
	'#type' => 'plaintext',
	'name' => 'q',
	'placeholder' => elgg_echo('elasticsearch:forms:admin_search:query:placeholder'),
]);

try {
	$status = $index_client->getIndexStatus();
} catch (Exception $e){
	elgg_log($e, 'ERROR');
	
	echo elgg_echo('elasticsearch:error:no_index');
	return;
}

$indices = array_keys($status);

echo elgg_view_field([
	'#type' => 'select',
	'name' => 'index',
	'options' => $indices,
	'value' => $index_client->getIndex(),
]);

$footer = elgg_view_field([
	'#type' => 'submit',
	'value' => elgg_echo('search'),
]);
elgg_set_form_footer($footer);
