<?php

use ColdTrick\ElasticSearch\Di\SearchService;

return [
	SearchService::name() => DI\object(SearchService::class)
		->constructor(DI\get('logger')),
];
