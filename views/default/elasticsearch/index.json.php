<?php
/*
 * Returns a json to be used in the index create ElasticSearch api call
 *
 * Uses $vars['index'] as the name of the index
 */

$index = elgg_extract('index', $vars);
if (empty($index)) {
	return;
}

$params = ['index' => $index];

$params['body']['settings']['analysis']['analyzer']['default'] = [
	'tokenizer'=> 'standard',
	'filter' => ['lowercase', 'asciifolding']
];

$params['body']['settings']['analysis']['analyzer']['case_insensitive_sort'] = [
	'tokenizer'=> 'keyword',
	'filter' => ['lowercase']
];

echo json_encode($params);