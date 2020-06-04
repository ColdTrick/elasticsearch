<?php

use ColdTrick\ElasticSearch\Di\SearchService;

$q = get_input('q');
$index = get_input('index');

/*
 * $q can be a string or a json formatted query like the following
 *
 * {
 *		"query": {
 *			"match" : {
 *				"name" : {
 *					"query" : "paul"
 *				}
 *			}
 *		}
 *	}
 *
 */

$client = SearchService::instance();
if (!$client->isClientReady()) {
	return elgg_error_response(elgg_echo('elasticsearch:error:no_client'));
}

$searchParams = [];

$json_data = @json_decode($q, true);

if (is_array($json_data)) {
	$searchParams['body'] = $json_data;
} else {
	$searchParams['body'] = [
		'query' => [
			'query_string' => [
				'query' => $q
			]
		]
	];
}

if (!empty($index)) {
	$searchParams['index'] = $index;
}

$result = $client->rawSearch($searchParams);
$content = '';
if (!empty($result)) {
	$content = elgg_view('elasticsearch/admin_search/result', [
		'result' => $result,
	]);
}

return elgg_ok_response($content);
