<?php

$guid = (int) get_input(guid);
if (empty($guid)) {
	register_error(elgg_echo('error:missing_data'));
	forward(REFERER);
}

$entity = get_entity($guid);
if (empty($entity)) {
	register_error(elgg_echo(''));
	forward(REFERER);
}

if (!$entity->getPrivateSetting(ELASTICSEARCH_INDEXED_NAME)) {
	// can't be reindexed as it hasn't been indexed yet (or shouldn't)
	register_error(elgg_echo('noaccess'));
	forward(REFERER);
}

$entity->setPrivateSetting(ELASTICSEARCH_INDEXED_NAME, 0);

system_message(elgg_echo('elasticsearch:action:admin:reindex_entity:success'));
forward(REFERER);
