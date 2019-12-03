<?php

use ColdTrick\ElasticSearch\Di\ClientService;

return [
	ClientService::name() => DI\object(ClientService::class)
		->constructor(DI\get('logger')),
];
