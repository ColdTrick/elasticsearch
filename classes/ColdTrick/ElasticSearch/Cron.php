<?php

namespace ColdTrick\ElasticSearch;

use ColdTrick\ElasticSearch\Di\IndexingService;
use ColdTrick\ElasticSearch\Di\SearchService;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Elgg\Database\QueryBuilder;

class Cron {
	
	/**
	 * Listen to the minute cron in order to sync data to ElasticSearch
	 *
	 * @param \Elgg\Hook $hook 'cron', 'minute'
	 *
	 * @return void
	 */
	public static function minuteSync(\Elgg\Hook $hook) {
		
		if (elgg_get_plugin_setting('sync', 'elasticsearch') !== 'yes') {
			// sync not enabled
			return;
		}
		
		$service = IndexingService::instance();
		if (!$service->isClientReady()) {
			return;
		}
		
		$max_run_time = 30;
		
		// delete first
		echo "Starting Elasticsearch indexing: delete" . PHP_EOL;
		elgg_log("Starting Elasticsearch indexing: delete", 'NOTICE');
		
		$service->bulkDeleteDocuments();
		
		// indexing actions
		foreach (IndexingService::INDEXING_TYPES as $action) {
			$batch_starttime = time();
			
			echo "Starting Elasticsearch indexing: {$action}" . PHP_EOL;
			elgg_log("Starting Elasticsearch indexing: {$action}", 'NOTICE');
			
			$service->bulkIndexDocuments([
				'type' => $action,
				'max_run_time' => $max_run_time,
			]);
			
			$max_run_time = $max_run_time - (time() - $batch_starttime);
			if ($max_run_time < 1) {
				break;
			}
		}
		
		echo 'Done with Elasticsearch indexing' . PHP_EOL;
		elgg_log('Done with Elasticsearch indexing', 'NOTICE');
	}
	
	/**
	 * Listen to the daily cron the do some cleanup jobs
	 *
	 * @param \Elgg\Hook $hook 'cron', 'daily'
	 *
	 * @return void
	 */
	public static function dailyCleanup(\Elgg\Hook $hook) {
		
		if (elgg_get_plugin_setting('sync', 'elasticsearch') !== 'yes') {
			// sync isn't enabled, so don't validate
			return;
		}
		
		if (elgg_get_plugin_setting('cron_validate', 'elasticsearch') !== 'yes') {
			// validate isn't enabled
			return;
		}
		
		echo 'Starting Elasticsearch cleanup: ES' . PHP_EOL;
		elgg_log('Starting Elasticsearch cleanup: ES', 'NOTICE');
		
		// find documents in ES which don't exist in Elgg anymore
		self::cleanupElasticsearch();
		
		echo 'Starting Elasticsearch cleanup: Elgg' . PHP_EOL;
		elgg_log('Starting Elasticsearch cleanup: Elgg', 'NOTICE');
		
		// find entities in Elgg which should be in ES but aren't
		self::checkElggIndex();
		
		echo 'Done with Elasticsearch cleanup' . PHP_EOL;
		elgg_log('Done with Elasticsearch cleanup', 'NOTICE');
	}
	
	/**
	 * Find documents in Elasticsearch which don't exist in Elgg anymore
	 *
	 * @return void
	 */
	protected static function cleanupElasticsearch() {
		
		$service = SearchService::instance();
		if (!$service->isClientReady()) {
			return;
		}
		
		// this could take a while
		set_time_limit(0);
		
		// prepare a search for all documents
		$search_params = [
			'index' => $service->getIndex(),
			'scroll' => '2m',
			'body' => [
				'query' => [
					'match_all' => (object) [],
				],
			],
		];
		
		try {
			$scroll_setup = $service->rawSearch($search_params);
		} catch (ElasticsearchException $e) {
			return;
		}
		
		// now scroll through all results
		$scroll_params = [
			'scroll' => '2m',
			'body' => [
				'scroll_id' => elgg_extract('_scroll_id', $scroll_setup),
			],
		];
		
		try {
			// ignore Elgg access
			elgg_call(ELGG_IGNORE_ACCESS, function() use ($service, &$scroll_params, $search_params) {
				
				$searchable_types = elasticsearch_get_registered_entity_types();
				
				while ($result = $service->scroll($scroll_params)) {
					// update scroll_id
					$new_scroll_id = elgg_extract('_scroll_id', $result);
					if (!empty($new_scroll_id)) {
						$scroll_params['body']['scroll_id'] = $new_scroll_id;
					}
					
					// process results
					$search_result = new SearchResult($result, $search_params);
					
					$elasticsearch_guids = $search_result->toGuids();
					if (empty($elasticsearch_guids)) {
						break;
					}
					
					// only validate searchable types, so unregistered types get removed from the index
					$elgg_guids = elgg_get_entities([
						'type_subtype_pairs' => $searchable_types ?: null,
						'guids' => $elasticsearch_guids,
						'limit' => false,
						'callback' => function ($row) {
							return (int) $row->guid;
						},
						'wheres' => [
							function (QueryBuilder $qb, $main_alias) {
								// banned users should not be indexed
								$md = $qb->joinMetadataTable($main_alias, 'guid', 'banned', 'left');
								
								return $qb->merge([
									$qb->compare("{$main_alias}.type", '!=', 'user', ELGG_VALUE_STRING),
									$qb->compare("{$md}.value", '=', 'no', ELGG_VALUE_STRING),
								], 'OR');
							},
						],
					]);
					
					$guids_not_in_elgg = array_diff($elasticsearch_guids, $elgg_guids);
					if (empty($guids_not_in_elgg)) {
						continue;
					}
					
					// remove all left over documents
					foreach ($guids_not_in_elgg as $guid) {
						
						// need to get the hist from Elasticsearch to get the type, since it's not in Elgg anymore
						$hit = $search_result->getHit($guid);
						
						elasticsearch_add_document_for_deletion($guid, [
							'_index' => $service->getIndex(),
							'_type' => elgg_extract('_type', $hit),
							'_id' => $guid,
						]);
					}
				}
			});
		} catch (ElasticsearchException $e) {
			// probably reached the end of the scroll
			// elgg_log('Elasticsearch cleanup: ' . $e->getMessage(), 'ERROR');
		}
		
		// clear scroll
		try {
			$service->clearScroll($scroll_params);
		} catch (ElasticsearchException $e) {
			// unable to clean, could be because we came to the end of the scroll
		}
	}
	
	/**
	 * Find entities in Elgg which aren't in Elasticsearch but should be
	 *
	 * @return void
	 */
	protected static function checkElggIndex() {
		
		// this could take a while
		set_time_limit(0);
		
		// ignore access
		elgg_call(ELGG_IGNORE_ACCESS, function() {
			// find unindexed GUIDs
			$guids = [];
			$unindexed = [];
			
			$batch = elgg_get_entities([
				'type_subtype_pairs' => elasticsearch_get_registered_entity_types_for_search(),
				'limit' => false,
				'batch' => true,
				'private_setting_name_value_pairs' => [
					'name' => ELASTICSEARCH_INDEXED_NAME,
					'value' => 0,
					'operand' => '>',
				],
				'callback' => function ($row) {
					return (int) $row->guid;
				},
			]);
			
			foreach ($batch as $guid) {
				$guids[] = $guid;
				
				if (count($guids) < 250) {
					continue;
				}
				
				$unindexed = array_merge($unindexed, self::findUnindexedGUIDs($guids));
				$guids = [];
			}
			
			if (!empty($guids)) {
				$unindexed = array_merge($unindexed, self::findUnindexedGUIDs($guids));
			}
			
			if (empty($unindexed)) {
				return;
			}
			
			// reindex entities
			// do this in chunks to prevent SQL-query limit hits
			$chunks = array_chunk($unindexed, 250);
			foreach ($chunks as $chunk) {
				$reindex = elgg_get_entities([
					'guids' => $chunk,
					'limit' => false,
					'batch' => true,
				]);
				/* @var $entity \ElggEntity */
				foreach ($reindex as $entity) {
					// mark for reindex
					$entity->setPrivateSetting(ELASTICSEARCH_INDEXED_NAME, 0);
				}
			}
		});
	}
	
	/**
	 * Find Elgg GUIDs not present in Elasticsearch
	 *
	 * @param int[] $guids Elgg GUIDs
	 *
	 * @return int[]
	 */
	protected static function findUnindexedGUIDs(array $guids = []) {
		
		if (empty($guids)) {
			return [];
		}
		
		$service = SearchService::instance();
		if (!$service->isClientReady()) {
			return;
		}
		
		$search_params = [
			'index' => $service->getIndex(),
			'size' => count($guids),
			'body' => [
				'query' => [
					'bool' => [
						'filter' => [
							'terms' => [
								'guid' => $guids,
							],
						],
					],
				],
			],
		];
		
		try {
			$es_result = $service->rawSearch($search_params);
			
			// process results
			$search_result = new SearchResult($es_result, $search_params);
			
			$elasticsearch_guids = $search_result->toGuids();
			
			return array_diff($guids, $elasticsearch_guids);
		} catch (ElasticsearchException $e) {
			// some error occured
		}
		
		return [];
	}
}
