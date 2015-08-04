<?php

elgg_load_css('elgg.icons');

// Elgg configuration
echo elgg_view('elasticsearch/stats/elgg');

// Elasticsearch stats require client
$client = elasticsearch_get_client();
if (empty($client)) {
	echo elgg_echo('elasticsearch:error:no_client');
	return;
}

// check if server is up
$alive = false;
try {
	$alive = $client->ping();
} catch (Exception $e) {
	
}

if (!$alive) {
	echo elgg_echo('elasticsearch:error:host_unavailable');
	return;
}

// cluster info
echo elgg_view('elasticsearch/stats/cluster', array('client' => $client));

// index info
echo elgg_view('elasticsearch/stats/indices', array('client' => $client));
