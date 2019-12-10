<?php

use ColdTrick\ElasticSearch\Di\IndexManagementService;
use ColdTrick\ElasticSearch\Di\IndexingService;
use ColdTrick\ElasticSearch\Di\SearchService;

return [
	IndexManagementService::name() => DI\object(IndexManagementService::class)
		->constructor(DI\get('logger'), DI\get('hooks')),
	IndexingService::name() => DI\object(IndexingService::class)
		->constructor(DI\get('logger'), DI\get('hooks')),
	SearchService::name() => DI\object(SearchService::class)
		->constructor(DI\get('logger'), DI\get('hooks')),
];
