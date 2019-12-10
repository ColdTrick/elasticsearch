<?php

use ColdTrick\ElasticSearch\Di\IndexManagementService;
use ColdTrick\ElasticSearch\Di\IndexingService;

return [
	IndexManagementService::name() => DI\object(IndexManagementService::class)
		->constructor(DI\get('logger'), DI\get('hooks')),
	IndexingService::name() => DI\object(IndexingService::class)
		->constructor(DI\get('logger'), DI\get('hooks')),
];
