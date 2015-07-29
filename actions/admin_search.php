<?php

$q = get_input('q');

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

$result = $client->search($searchParams);
if ($result) {
	echo elgg_view('elasticsearch/admin_search/result', ['result' => $result]);
}