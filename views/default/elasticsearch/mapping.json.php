<?php
/*
 * Returns a json to be used in the putMapping ElasticSearch api call
 *
 * Uses $vars['index'] as the name of the index
 */

$index = elgg_extract('index', $vars);
if (empty($index)) {
	return;
}

$properties = [
	'name' => [
		'type' => 'string',
		'index' => 'not_analyzed',
		'copy_to' => 'title'
	],
	'description' => [
		'type' => 'string'
	],
	'relationships' => [
		'type' => 'nested',
	],
	'metadata' => [
		'type' => 'nested',
	],
	'profile' => [
		'type' => 'nested',
	],
];
	
$dynamic_templates = [
	[
		'strings' => [
			'match_mapping_type' => 'string',
			'mapping' => [
				'type' => 'string',
				'fields' => [
					'raw' => [
						'type' => 'string',
						'analyzer' => 'case_insensitive_sort',
						'ignore_above' => 256,
					]
				]
			]
		],
	],
	[
		'profile_strings' => [
			'path_match' => 'profile.*',
			'mapping' => [
				'type' => 'string',
			],
		],
	],
];

$mapping = [
	'index' => $index,
	'type' => '_default_',
	'body' => [
		'_default_' => [
			'dynamic_templates' => $dynamic_templates,
			'properties' => $properties,
		]
	]
];

echo json_encode($mapping);