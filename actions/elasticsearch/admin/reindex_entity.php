<?php

$guid = (int) get_input('guid');
if (empty($guid)) {
	return elgg_error_response(elgg_echo('error:missing_data'));
}

$entity = get_entity($guid);
if (empty($entity)) {
	return elgg_error_response(elgg_echo('error:missing_data'));
}

if (!$entity->getPrivateSetting(ELASTICSEARCH_INDEXED_NAME)) {
	// can't be reindexed as it hasn't been indexed yet (or shouldn't)
	return elgg_error_response(elgg_echo('save:fail'));
}

$entity->setPrivateSetting(ELASTICSEARCH_INDEXED_NAME, 0);

return elgg_ok_response('', elgg_echo('elasticsearch:action:admin:reindex_entity:success'));
