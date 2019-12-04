<?php

use ColdTrick\ElasticSearch\Di\IndexManagementService;

// Elgg configuration
echo elgg_view('elasticsearch/stats/elgg');

// Elasticsearch stats require configured client
$service = IndexManagementService::instance();
if (!$service->isClientReady()) {
	echo elgg_echo('elasticsearch:error:no_client');
	return;
}

// check if server is up
if (!$service->ping()) {
	echo elgg_echo('elasticsearch:error:host_unavailable');
	return;
}

// cluster info
echo elgg_view('elasticsearch/stats/cluster', ['service' => $service]);

// index info
echo elgg_view('elasticsearch/stats/indices', ['service' => $service]);
