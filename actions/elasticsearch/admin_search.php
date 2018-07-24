<?php

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

$client = elasticsearch_get_client();
if (!$client) {
	return elgg_error_response(elgg_echo('elasticsearch:error:no_client'));
}

$searchParams = [];

$json_data = @json_decode($q, true);

if (is_array($json_data)) {
	$searchParams['body'] = $json_data;
} else {
	$searchParams['body'] = [
		'query' => [
			'match' => [
				'_all' => $q
			]
		]
	];
}

if (!empty($index)) {
	$searchParams['index'] = $index;
}

$result = $client->search($searchParams);
$content = '';
if ($result) {
	$content = elgg_view('elasticsearch/admin_search/result', ['result' => $result]);
}

return elgg_ok_response($content);
