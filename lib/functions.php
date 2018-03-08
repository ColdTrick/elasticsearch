<?php
/**
 * All helper functions are bundled here
 */

/**
 * Function to (un)register various search hooks
 */
function elastic_prepare_search_hooks() {
	// unregister default search hooks
	elgg_unregister_plugin_hook_handler('search', 'object', 'search_objects_hook');
	elgg_unregister_plugin_hook_handler('search', 'user', 'search_users_hook');
	elgg_unregister_plugin_hook_handler('search', 'group', 'search_groups_hook');
	elgg_unregister_plugin_hook_handler('search', 'tags', 'search_tags_hook');

	// register elastic search hooks
	elgg_register_plugin_hook_handler('search', 'group', ['ColdTrick\ElasticSearch\SearchHooks', 'searchEntities']);
	elgg_register_plugin_hook_handler('search', 'user', ['ColdTrick\ElasticSearch\SearchHooks', 'searchEntities']);
	elgg_register_plugin_hook_handler('search', 'object', ['ColdTrick\ElasticSearch\SearchHooks', 'searchEntities']);
	elgg_register_plugin_hook_handler('search', 'tags', ['ColdTrick\ElasticSearch\SearchHooks', 'searchTags']);
	elgg_register_plugin_hook_handler('search', 'combined:all', ['ColdTrick\ElasticSearch\SearchHooks', 'searchEntities'], 400);

	// register fallback to default search hooks
	elgg_register_plugin_hook_handler('search', 'object', ['ColdTrick\ElasticSearch\SearchHooks', 'searchFallback'], 9000);
	elgg_register_plugin_hook_handler('search', 'user', ['ColdTrick\ElasticSearch\SearchHooks', 'searchFallback'], 9000);
	elgg_register_plugin_hook_handler('search', 'group', ['ColdTrick\ElasticSearch\SearchHooks', 'searchFallback'], 9000);
	elgg_register_plugin_hook_handler('search', 'tags', ['ColdTrick\ElasticSearch\SearchHooks', 'searchFallback'], 9000);
}

/**
 * Get a working ElasticSearch client for further use
 *
 * @return false|ColdTrick\ElasticSearch\Client
 */
function elasticsearch_get_client() {
	static $client;
	
	if (!isset($client)) {
		$client = false;
		
		// Check if the function 'curl_multi_exec' isn't blocked (for security reasons), this prevents error_log overflow
		// this isn't caught by the \Elasticseach\Client
		if (!function_exists('curl_multi_exec')) {
			return false;
		}
		
		$host = elasticsearch_get_setting('host');
		if (!empty($host)) {
			$params = [];
			
			$hosts = explode(',', $host);
			array_walk($hosts, 'elasticsearch_cleanup_host');
			
			$params['hosts'] = $hosts;
			
			$params['logging'] = true;
			$params['logObject'] = new ColdTrick\ElasticSearch\DatarootLogger('log');
			
			// trigger hook so other plugins can infuence the params
			$params = elgg_trigger_plugin_hook('params', 'elasticsearch', $params, $params);
			
			try {
				$client = new ColdTrick\ElasticSearch\Client($params);
			} catch (Exception $e) {
				elgg_log("Unable to create ElasticSearch client: {$e->getMessage()}", 'ERROR');
			}
		}
	}
	
	return $client;
}

/**
 * Get the type/subtypes to index in ElasticSearch
 *
 *  @return false|array
 */
function elasticsearch_get_registered_entity_types() {
	
	$type_subtypes = get_registered_entity_types();
	foreach ($type_subtypes as $type => $subtypes) {
		if (empty($subtypes)) {
			// repair so it can be used in elgg_get_entities*
			$type_subtypes[$type] = ELGG_ENTITIES_ANY_VALUE;
		}
	}
	
	return elgg_trigger_plugin_hook('index_entity_type_subtypes', 'elasticsearch', $type_subtypes, $type_subtypes);
}

/**
 * Get the type/subtypes for search in ElasticSearch
 *
 *  @return false|array
 */
function elasticsearch_get_registered_entity_types_for_search() {

	$type_subtypes = get_registered_entity_types();
	foreach ($type_subtypes as $type => $subtypes) {
		if (empty($subtypes)) {
			// repair so it can be used in elgg_get_entities*
			$type_subtypes[$type] = ELGG_ENTITIES_ANY_VALUE;
		}
	}

	return elgg_trigger_plugin_hook('search', 'type_subtype_pairs', $type_subtypes, $type_subtypes);
}

/**
 * Get the $options for elgg_get_entities* in order to update the ElasticSearch index
 *
 * @param string $type which options to get
 *
 * @return false|array
 */
function elasticsearch_get_bulk_options($type = 'no_index_ts') {
	
	$type_subtypes = elasticsearch_get_registered_entity_types();
	if (empty($type_subtypes)) {
		return false;
	}
	
	$dbprefix = elgg_get_config('dbprefix');
	
	switch ($type) {
		case 'no_index_ts':
			// new or updated entities
			return [
				'type_subtype_pairs' => $type_subtypes,
				'limit' => false,
				'wheres' => [
					"e.guid NOT IN (
						SELECT ps.entity_guid
						FROM {$dbprefix}private_settings ps
						WHERE ps.name = '" . ELASTICSEARCH_INDEXED_NAME . "'
					)",
					"e.guid NOT IN (
						SELECT ue.guid
						FROM {$dbprefix}users_entity ue
						WHERE ue.banned = 'yes'
					)",
				],
			];
			
			break;
		case 'reindex':
			// a reindex has been initiated, so update all out of date entities
			$setting = (int) elasticsearch_get_setting('reindex_ts');
			if (empty($setting)) {
				return false;
			}
			
			return [
				'type_subtype_pairs' => $type_subtypes,
				'limit' => false,
				'private_setting_name_value_pairs' => [
					[
						'name' => ELASTICSEARCH_INDEXED_NAME,
						'value' => $setting,
						'operand' => '<'
					],
					[
						'name' => ELASTICSEARCH_INDEXED_NAME,
						'value' => 0,
						'operand' => '>'
					],
				],
			];
			
			break;
		case 'update':
			// content that was updated in Elgg and needs to be reindexed
			return [
				'type_subtype_pairs' => $type_subtypes,
				'limit' => false,
				'private_setting_name_value_pairs' => [
					[
						'name' => ELASTICSEARCH_INDEXED_NAME,
						'value' => 0,
					],
				],
			];
			
			break;
		case 'count':
			// content that needs to be indexed
			return [
				'type_subtype_pairs' => $type_subtypes,
				'count' => true,
			];
			
			break;
	}
	
	return false;
}

/**
 * Do some cleanup on the host for use in the ElasticSearch client
 *
 * @param string $host the host url
 *
 * @return void
 */
function elasticsearch_cleanup_host(&$host) {
	// Elgg saves html encoded
	$host = html_entity_decode($host, ENT_QUOTES, 'UTF-8');

	// remove spaces
	$host = trim($host);

	// remove trailing / (ElasticSearch adds it again)
	$host = rtrim($host, '/');
}

/**
 * Get a plugin setting
 *
 * This function caches all plugin settings for efficientcy
 *
 * @param string $setting the plugin setting to get
 *
 * @return null|string
 */
function elasticsearch_get_setting($setting) {
	static $settings;
	
	if (!isset($settings)) {
		// default settings
		$settings = [
			'host' => '',
			'index' => '',
			'search_alias' => '',
			'sync' => 'no',
			'search' => 'no',
			'cron_validate' => 'no',
		];
		
		$plugin = elgg_get_plugin_from_id('elasticsearch');
		$plugin_settings = $plugin->getAllSettings();
		if (!empty($plugin_settings)) {
			$settings = array_merge($settings, $plugin_settings);
		}
	}
	
	return elgg_extract($setting, $settings);
}

/**
 * Saves an array of documents to be deleted from the elastic index
 *
 * @param int   $guid guid of the document to be deleted
 * @param array $info an array of information needed to be saved to be able to delete it from the index
 *
 * @return void
 */
function elasticsearch_add_document_for_deletion($guid, $info) {
	
	if (empty($guid) || !is_array($info)) {
		return;
	}
	
	$plugin = elgg_get_plugin_from_id('elasticsearch');
	
	$fh = new ElggFile();
	$fh->owner_guid = $plugin->getGUID();
	$fh->setFilename("documents_for_deletion/{$guid}");
	
	// set a timestamp for deletion
	$info['time'] = time();
	
	if ($fh->open("write")) {
		$fh->write(serialize($info));
		$fh->close();
	}
}

/**
 * Removes a file based on a guid
 *
 * @param int $guid guid of the document to be deleted
 *
 * @return void
 */
function elasticsearch_remove_document_for_deletion($guid) {
	
	if (empty($guid)) {
		return;
	}
	
	$plugin = elgg_get_plugin_from_id('elasticsearch');
	
	$fh = new ElggFile();
	$fh->owner_guid = $plugin->getGUID();
	$fh->setFilename("documents_for_deletion/{$guid}");
	
	if ($fh->exists()) {
		$fh->delete();
	}
}

/**
 * Returns an array of documents to be deleted from the elastic index
 *
 * @return array
 */
function elasticsearch_get_documents_for_deletion() {
	$plugin = elgg_get_plugin_from_id('elasticsearch');
	
	$locator = new \Elgg\EntityDirLocator($plugin->getGUID());
	$documents_path = elgg_get_data_path() . $locator->getPath() . 'documents_for_deletion/';
	
	$dir = @opendir($documents_path);
	if (!$dir) {
		return [];
	}
	
	$documents = [];
	while (($file = readdir($dir)) !== false) {
		if (is_dir($file)) {
			continue;
		}
		
		$contents = unserialize(file_get_contents($documents_path . $file));
		if (!is_array($contents)) {
			continue;
		}
		
		$deletion_time = elgg_extract('time', $contents);
		if (!empty($deletion_time) && $deletion_time > time()) {
			// not yet scheduled for deletion, (only if deletion failed once before)
			continue;
		}
		
		unset($contents['time']);
		
		$documents[$file] = $contents;
	}
	
	return $documents;
}

/**
 * Reschedule a document for deletion, because something failed
 *
 * @param int $guid the document to be rescheduled
 *
 * @return void
 */
function elasticsearch_reschedule_document_for_deletion($guid) {
	
	$plugin = elgg_get_plugin_from_id('elasticsearch');
	
	$fh = new ElggFile();
	$fh->owner_guid = $plugin->getGUID();
	$fh->setFilename("documents_for_deletion/{$guid}");
	
	if (!$fh->exists()) {
		// shouldn't happen
		return;
	}
	
	$contents = $fh->grabFile();
	if (empty($contents)) {
		return;
	}
	
	$contents = unserialize($contents);
	if (!is_array($contents)) {
		return;
	}
	
	// try agin in an hour
	$contents['time'] = time() + (60 * 60);
	
	$fh->open('write');
	$fh->write(serialize($contents));
	$fh->close();
}

/**
 * Make inspection values into a table structure
 *
 * @param mixed $key           the key to present
 * @param array $merged_values the base array to show from
 * @param array $elgg_values   the Elgg values
 * @param array $elgg_values   the Elasticsearch values
 * @param int   $depth         internal usage only
 *
 * @return false|array
 */
function elasticsearch_inspect_show_values($key, $merged_values, $elgg_values, $elasticsearch_values, $depth = 0) {
	
	if (empty($merged_values) || !is_array($merged_values)) {
		return false;
	}
	
	$rows = [];
	if (empty($depth)) {
		$rows[] = elgg_format_element('th', ['colspan' => 3], $key);
	} else {
		$rows[] = elgg_format_element('td', ['colspan' => 3], "<b>{$key}</b>");
	}
	
	foreach ($merged_values as $key => $values) {
		if (!is_array($values)) {
			$rows[] = implode('', [
				elgg_format_element('td', [], $key),
				elgg_format_element('td', [], elgg_extract($key, $elgg_values)),
				elgg_format_element('td', [], elgg_extract($key, $elasticsearch_values)),
			]);
		} else {
			$subvalues = elasticsearch_inspect_show_values($key, $values, elgg_extract($key, $elgg_values), elgg_extract($key, $elasticsearch_values), $depth + 1);
			if (!empty($subvalues)) {
				$rows = array_merge($rows, $subvalues);
			}
		}
	}
	
	return $rows;
}
