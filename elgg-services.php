<?php

use ColdTrick\ElasticSearch\Di\IndexManagementService;

return [
	IndexManagementService::name() => DI\object(IndexManagementService::class)
		->constructor(DI\get('logger')),
];
