<?php
/**
 * Schedule an entity to be removed from the index
 */

$guid = (int) get_input('guid');
if ($guid < 1) {
	return elgg_error_response(elgg_echo('error:missing_data'));
}

$client = elasticsearch_get_client();
if (empty($client)) {
	return elgg_error_response(elgg_echo('elasticsearch:error:no_client'));
}

$es_data = $client->inspect($guid, true);
if (empty($es_data)) {
	return elgg_error_response(elgg_echo('elasticsearch:error:search'));
}

elasticsearch_add_document_for_deletion($guid, [
	'_index' => elgg_extract('_index', $es_data),
	'_type' => elgg_extract('_type', $es_data),
	'_id' => $guid,
]);

return elgg_ok_response('', elgg_echo('elasticsearch:action:admin:delete_entity:success'));
