<?php

$index = get_input('index');
$task = get_input('task');
if (empty($index)) {
	return elgg_error_response(elgg_echo('elasticsearch:error:no_index'));
}

$client = elasticsearch_get_client();
if (empty($client)) {
	return elgg_error_response(elgg_echo('elasticsearch:error:no_client'));
}

$exists = false;
try {
	$exists = $client->indices()->exists(['index' => $index]);
} catch (Exception $e) {
	// something is wrong
	elgg_log($e, 'NOTICE');
}

switch ($task) {
	case 'flush':
		
		if (!$exists) {
			return elgg_error_response(elgg_echo('elasticsearch:error:index_not_exists', [$index]));
		}
		
		try {
			$client->indices()->flush(['index' => $index]);
		} catch (Exception $e) {
			return elgg_error_response(elgg_echo('elasticsearch:action:admin:index_management:error:flush', [$index]));
		}
		
		return elgg_ok_response('', elgg_echo('elasticsearch:action:admin:index_management:flush', [$index]));
	case 'optimize':
		
		if (!$exists) {
			return elgg_error_response(elgg_echo('elasticsearch:error:index_not_exists', [$index]));
		}
		
		try {
			$client->indices()->optimize(array(
				'index' => $index,
				'max_num_segments' => 1,
				'wait_for_merge' => false
			));
		} catch (Exception $e) {
			return elgg_error_response(elgg_echo('elasticsearch:action:admin:index_management:error:optimize', [$index]));
		}
		
		return elgg_ok_response('', elgg_echo('elasticsearch:action:admin:index_management:optimize', [$index]));
	case 'delete':
		
		if (!$exists) {
			return elgg_error_response(elgg_echo('elasticsearch:error:index_not_exists', [$index]));
		}
		
		try {
			$client->indices()->delete(['index' => $index]);
		} catch (Exception $e) {
			return elgg_error_response(elgg_echo('elasticsearch:action:admin:index_management:error:delete', [$index]));
		}
		
		return elgg_ok_response('', elgg_echo('elasticsearch:action:admin:index_management:delete', [$index]));
	case 'create':
		
		if ($exists) {
			return elgg_error_response(elgg_echo('elasticsearch:action:admin:index_management:error:create:exists', [$index]));
		}
		
		try {
			$params = json_decode(elgg_view('elasticsearch/index.json', ['index' => $index]), true);
			
			$client->indices()->create($params);
		} catch (Exception $e) {
			return elgg_error_response(elgg_echo('elasticsearch:action:admin:index_management:error:create', [$index]));
		}
		
		return elgg_ok_response('', elgg_echo('elasticsearch:action:admin:index_management:create', [$index]));
	case 'add_mappings':
		
		if (!$exists) {
			return elgg_error_response(elgg_echo('elasticsearch:error:index_not_exists', [$index]));
		}
		
		try {
			$mapping = json_decode(elgg_view('elasticsearch/mapping.json', ['index' => $index]), true);
			
			// Update the index mapping
			$client->indices()->putMapping($mapping);
		} catch (Exception $e) {
			register_error($e->getMessage());
			return elgg_error_response(elgg_echo('elasticsearch:action:admin:index_management:error:add_mappings', [$index]));
		}
		
		return elgg_ok_response('', elgg_echo('elasticsearch:action:admin:index_management:add_mappings', [$index]));
	case 'add_alias':
		
		if (!$exists) {
			return elgg_error_response(elgg_echo('elasticsearch:error:index_not_exists', [$index]));
		}
		
		$alias = elgg_get_plugin_setting('search_alias', 'elasticsearch');
		if (empty($alias)) {
			return elgg_error_response(elgg_echo('elasticsearch:error:alias_not_configured'));
		}
		
		$alias_exists = $client->indices()->existsAlias([
			'name' => $alias,
			'index' => $index,
		]);
		if ($alias_exists) {
			return elgg_error_response(elgg_echo('elasticsearch:action:admin:index_management:error:add_alias:exists', [$alias, $index]));
		}
		
		try {
			$client->indices()->putAlias([
				'index' => $index,
				'name' => $alias,
			]);
		} catch (Exception $e) {
			return elgg_error_response(elgg_echo('elasticsearch:action:admin:index_management:error:add_alias', [$alias, $index]));
		}
		
		return elgg_ok_response('', elgg_echo('elasticsearch:action:admin:index_management:add_alias', [$alias, $index]));
	case 'delete_alias':
		
		if (!$exists) {
			return elgg_error_response(elgg_echo('elasticsearch:error:index_not_exists', [$index]));
		}
		
		$alias = elgg_get_plugin_setting('search_alias', 'elasticsearch');
		if (empty($alias)) {
			return elgg_error_response(elgg_echo('elasticsearch:error:alias_not_configured'));
		}
		
		$alias_exists = $client->indices()->existsAlias([
			'name' => $alias,
			'index' => $index,
		]);
		if (!$alias_exists) {
			return elgg_error_response(elgg_echo('elasticsearch:action:admin:index_management:error:delete_alias:exists', [$alias, $index]));
		}
		
		try {
			$client->indices()->deleteAlias([
				'index' => $index,
				'name' => $alias,
			]);
		} catch (Exception $e) {
			return elgg_error_response(elgg_echo('elasticsearch:action:admin:index_management:error:delete_alias', [$alias, $index]));
		}
		
		return elgg_ok_response('', elgg_echo('elasticsearch:action:admin:index_management:delete_alias', [$alias, $index]));
}

return elgg_error_response(elgg_echo('elasticsearch:action:admin:index_management:error:task', [$task]));
