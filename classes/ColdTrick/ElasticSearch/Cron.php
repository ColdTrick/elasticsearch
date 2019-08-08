<?php

namespace ColdTrick\ElasticSearch;

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
		
		$client = elasticsearch_get_client();
		if (empty($client)) {
			return;
		}
		
		$starttime = (int) $hook->getParam('time', time());
		
		// delete first
		$client->bulkDeleteDocuments();
		
		$update_actions = [
			'no_index_ts',
			'update',
			'reindex',
		];
		foreach ($update_actions as $action) {
			
			echo "Starting Elasticsearch indexing: {$action}" . PHP_EOL;
			elgg_log("Starting Elasticsearch indexing: {$action}", 'NOTICE');
			
			$options = elasticsearch_get_bulk_options($action);
			if (empty($options)) {
				continue;
			}
			
			$time_left = self::batchSync($options, $starttime);
			if ($time_left === false) {
				break;
			}
		}
		
		echo 'Done with Elasticsearch indexing' . PHP_EOL;
		elgg_log('Done with Elasticsearch indexing', 'NOTICE');
	}
	
	/**
	 * Batch sync data to ElasticSearch
	 *
	 * This function is timed at a max runtime of 30sec
	 *
	 * @param array  $options  the options for elgg_get_entities()
	 * @param int    $crontime the starttime of the cron in order to limit max runtime
	 *
	 * @return bool|void
	 */
	protected static function batchSync($options, $crontime) {
		
		if (empty($options) || !is_array($options)) {
			return;
		}
		
		$client = elasticsearch_get_client();
		if (empty($client)) {
			return;
		}
		
		$crontime = (int) $crontime;
		if ($crontime < 1) {
			$crontime = time();
		}
		
		if ((time() - $crontime) >= 30) {
			return false;
		}
		
		set_time_limit(40);
		
		$batch_size = 100;
		
		$options['limit'] = $batch_size;
		
		return elgg_call(ELGG_IGNORE_ACCESS, function() use ($options, $crontime, $client) {
			$time_left = true;
			
			$mark_entity_done = function (\ElggEntity $entity) {
				$entity->setPrivateSetting(ELASTICSEARCH_INDEXED_NAME, time());
				$entity->invalidateCache();
			};
			
			while ($time_left && ($entities = elgg_get_entities($options))) {
				
				foreach ($entities as $index => $entity) {
					$params = [
						'entity' => $entity,
					];
					
					if ((bool) elgg_trigger_plugin_hook('index:entity:prevent', 'elasticsearch', $params, false)) {
						$mark_entity_done($entity);
						
						unset($entities[$index]);
						continue;
					}
				}
				
				$result = $client->bulkIndexDocuments($entities);
				if (empty($result)) {
					break;
				}
				
				$items = elgg_extract('items', $result);
				foreach ($items as $item) {
					$guid = (int) elgg_extract('_id', elgg_extract('index', $item));
					$status = elgg_extract('status', elgg_extract('index', $item));
					
					if ($status !== 200) {
						continue;
					}
					
					if (empty($guid)) {
						continue;
					}
					
					$entity = get_entity($guid);
					if (!$entity instanceof \ElggEntity) {
						continue;
					}
					
					$mark_entity_done($entity);
				}
				
				if ((time() - $crontime) >= 30) {
					$time_left = false;
					break;
				}
			}
			
			return $time_left;
		});
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
		
		$client = elasticsearch_get_client();
		if (empty($client)) {
			return;
		}
		
		// this could take a while
		set_time_limit(0);
		
		// prepare a search for all documents
		$search_params = [
			'index' => $client->getIndex(),
			'search_type' => 'scan',
			'scroll' => '2m',
			'body' => [
				'query' => [
					'match_all' => [],
				],
			],
		];
		
		try {
			$scroll_setup = $client->search($search_params);
		} catch (\Exception $e) {
			return;
		}
		
		// now scroll through all results
		$scroll_params = [
			'scroll_id' => elgg_extract('_scroll_id', $scroll_setup),
			'scroll' => '2m',
		];
		
		try {
			// ignore Elgg access
			elgg_call(ELGG_IGNORE_ACCESS, function() use ($client, &$scroll_params, $search_params) {
				
				$searchable_types = elasticsearch_get_registered_entity_types();
				
				while ($result = $client->scroll($scroll_params)) {
					// update scroll_id
					$new_scroll_id = elgg_extract('_scroll_id', $result);
					if (!empty($new_scroll_id)) {
						$scroll_params['scroll_id'] = $new_scroll_id;
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
							'_index' => $client->getIndex(),
							'_type' => elgg_extract('_type', $hit),
							'_id' => $guid,
						]);
					}
				}
			});
		} catch (\Exception $e) {
			// probably reached the end of the scroll
			// elgg_log('Elasticsearch cleanup: ' . $e->getMessage(), 'ERROR');
		}
		
		// clear scroll
		try {
			$client->clearScroll($scroll_params);
		} catch (\Exception $e) {
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
	protected static function findUnindexedGUIDs($guids = []) {
		
		if (empty($guids) || !is_array($guids)) {
			return [];
		}
		
		$client = elasticsearch_get_client();
		if (empty($client)) {
			return;
		}
		
		$search_params = [
			'index' => $client->getIndex(),
			'size' => count($guids),
			'body' => [
				'query' => [
					'filtered' => [
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
			$es_result = $client->search($search_params);
			
			// process results
			$search_result = new SearchResult($es_result, $search_params);
			
			$elasticsearch_guids = $search_result->toGuids();
			
			return array_diff($guids, $elasticsearch_guids);
		} catch (\Exception $e) {
			// some error occured
		}
		
		return [];
	}
}
