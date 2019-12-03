<?php

use ColdTrick\ElasticSearch\Bootstrap;
use ColdTrick\ElasticSearch\Upgrades\RemoveLogs;

define('ELASTICSEARCH_INDEXED_NAME', 'elasticsearch_last_indexed');

require_once(__DIR__ . '/lib/functions.php');

return [
	'bootstrap' => Bootstrap::class,
	'actions' => [
		'elasticsearch/admin_search' => [
			'access' => 'admin',
		],
		'elasticsearch/admin/index_management' => [
			'access' => 'admin',
		],
		'elasticsearch/admin/reindex' => [
			'access' => 'admin',
		],
		'elasticsearch/admin/download_log' => [
			'access' => 'admin',
		],
		'elasticsearch/admin/reindex_entity' => [
			'access' => 'admin',
		],
		'elasticsearch/admin/delete_entity' => [
			'access' => 'admin',
		],
	],
	'settings' => [
		'sync' => 'no',
		'search' => 'no',
		'search_score' => 'no',
		'cron_validate' => 'no',
		'ignore_ssl' => 0,
		'decay_time_field' => 'time_created',
	],
	'upgrades' => [
		RemoveLogs::class,
	],
];
