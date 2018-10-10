<?php
/**
 * All helper functions are bundled here
 */

use Elgg\Database\QueryBuilder;
use Elgg\Database\Select;

/**
 * Get a working ElasticSearch client for further use
 *
 * @return false|ColdTrick\ElasticSearch\Client
 */
function elasticsearch_get_client() {
	static $client;
	
	if (isset($client)) {
		return $client;
	}
	
	$client = false;
	
	// Check if the function 'curl_multi_exec' isn't blocked (for security reasons), this prevents error_log overflow
	// this isn't caught by the \Elasticseach\Client
	if (!function_exists('curl_multi_exec')) {
		return false;
	}
	
	$host = elgg_get_plugin_setting('host', 'elasticsearch');
	if (empty($host)) {
		return false;
	}
	
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
 * Returns the boostable types for ElasticSearch
 *
 *  @return array
 */
function elasticsearch_get_types_for_boosting() {
	$type_subtypes = elasticsearch_get_registered_entity_types_for_search();
	$types = \ColdTrick\ElasticSearch\SearchHooks::entityTypeSubtypesToSearchTypes($type_subtypes);
	
	return elgg_trigger_plugin_hook('boostable_types', 'elasticsearch', $types, $types);
}

/**
 * Get the $options for elgg_get_entities in order to update the ElasticSearch index
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
	
	switch ($type) {
		case 'no_index_ts':
			// new or updated entities
			return [
				'type_subtype_pairs' => $type_subtypes,
				'limit' => false,
				'wheres' => [
					function (QueryBuilder $qb, $main_alias) {
						$select = Select::fromTable('private_settings', 'ps');
						$select->select('ps.entity_guid')
							->where($qb->compare('ps.name', '=', ELASTICSEARCH_INDEXED_NAME, ELGG_VALUE_STRING));
						
						return $qb->compare("{$main_alias}.guid", 'NOT IN', $select->getSQL());
					},
					function (QueryBuilder $qb, $main_alias) {
						$select = Select::fromTable('metadata', 'b');
						$select->select('b.entity_guid')
							->joinEntitiesTable('b', 'entity_guid', 'inner', 'be');
						$select->where($qb->compare('be.type', '=', 'user', ELGG_VALUE_STRING))
							->andWhere($qb->compare('b.name', '=', 'banned', ELGG_VALUE_STRING))
							->andWhere($qb->compare('b.value', '=', 'yes', ELGG_VALUE_STRING));
						
						return $qb->compare("{$main_alias}.guid", 'NOT IN', $select->getSQL());
					},
				],
			];
			
			break;
		case 'reindex':
			// a reindex has been initiated, so update all out of date entities
			$setting = (int) elgg_get_plugin_setting('reindex_ts', 'elasticsearch');
			if ($setting < 1) {
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
 * Saves an array of documents to be deleted from the elastic index
 *
 * @param int   $guid guid of the document to be deleted
 * @param array $info an array of information needed to be saved to be able to delete it from the index
 *
 * @return void
 */
function elasticsearch_add_document_for_deletion($guid, $info) {
	
	$guid = (int) $guid;
	if ($guid < 1 || !is_array($info)) {
		return;
	}
	
	$plugin = elgg_get_plugin_from_id('elasticsearch');
	
	$fh = new ElggFile();
	$fh->owner_guid = $plugin->guid;
	$fh->setFilename("documents_for_deletion/{$guid}");
	
	// set a timestamp for deletion
	$info['time'] = time();
	
	if ($fh->open('write')) {
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
	
	$guid = (int) $guid;
	if ($guid < 1) {
		return;
	}
	
	$plugin = elgg_get_plugin_from_id('elasticsearch');
	
	$fh = new ElggFile();
	$fh->owner_guid = $plugin->guid;
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
	
	$locator = new \Elgg\EntityDirLocator($plugin->guid);
	$documents_path = elgg_get_data_path() . $locator->getPath() . 'documents_for_deletion/';
	
	try {
		$dir = new DirectoryIterator($documents_path);
	} catch (Exception $e) {
		return [];
	}
	
	$documents = [];
	/* @var $fileinfo SplFileInfo */
	foreach ($dir as $fileinfo) {
		if (!$fileinfo->isFile() || !$fileinfo->isReadable()) {
			continue;
		}
		
		$fh = $fileinfo->openFile('r');
		$contents = $fh->fread($fh->getSize());
		unset($fh); // closes file handler
		if (empty($contents)) {
			continue;
		}
		
		$contents = unserialize($contents);
		if (!is_array($contents)) {
			continue;
		}
		
		$deletion_time = elgg_extract('time', $contents);
		if (!empty($deletion_time) && $deletion_time > time()) {
			// not yet scheduled for deletion, (only if deletion failed once before)
			continue;
		}
		
		unset($contents['time']);
		
		$documents[$fileinfo->getFilename()] = $contents;
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
	
	$guid = (int) $guid;
	if ($guid < 1) {
		return;
	}
	
	$plugin = elgg_get_plugin_from_id('elasticsearch');
	
	$fh = new ElggFile();
	$fh->owner_guid = $plugin->guid;
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
	
	// try again in an hour
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
		$rows[] = elgg_format_element('td', ['colspan' => 3], elgg_format_element('b', [], $key));
	}
	
	foreach ($merged_values as $key => $values) {
		if (is_array($values)) {
			$subvalues = elasticsearch_inspect_show_values($key, $values, elgg_extract($key, $elgg_values), elgg_extract($key, $elasticsearch_values), $depth + 1);
			if (empty($subvalues)) {
				continue;
			}
			
			$rows = array_merge($rows, $subvalues);
			continue;
		}
		
		$elgg_value = elgg_extract($key, $elgg_values);
		if (is_array($elgg_value)) {
			$elgg_value = implode(', ', $elgg_value);
		}
		
		$rows[] = implode(PHP_EOL, [
			elgg_format_element('td', [], $key),
			elgg_format_element('td', [], $elgg_value),
			elgg_format_element('td', [], elgg_extract($key, $elasticsearch_values)),
		]);
	}
	
	return $rows;
}
