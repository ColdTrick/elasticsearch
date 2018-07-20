<?php

$client = elasticsearch_get_client();
if (empty($client)) {
	echo elgg_echo('elasticsearch:error:no_client');
	return;
}

elgg_require_js('elasticsearch/admin_search');

echo elgg_view_field([
	'#type' => 'plaintext',
	'name' => 'q',
	'placeholder' => elgg_echo('elasticsearch:forms:admin_search:query:placeholder'),
]);

try {
	$status = $client->indices()->status();
} catch (Exception $e){
	elgg_log($e, 'ERROR');
}

$indices = array_keys(elgg_extract('indices', $status));

echo elgg_view_field([
	'#type' => 'select',
	'name' => 'index',
	'options' => $indices,
	'value' => $client->getIndex(),
]);

$footer = elgg_view_field([
	'#type' => 'submit',
	'value' => elgg_echo('search'),
]);
elgg_set_form_footer($footer);
