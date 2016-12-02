<?php

namespace ColdTrick\ElasticSearch;

class Cron {
	
	/**
	 * Listen to the minute cron in order to sync data to ElasticSearch
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function minuteSync($hook, $type, $returnvalue, $params) {
		
		$setting = elasticsearch_get_setting('sync');
		if ($setting !== 'yes') {
			// sync not enabled
			return;
		}
		
		$client = elasticsearch_get_client();
		if (empty($client)) {
			return;
		}
		
		$starttime = (int) elgg_extract('time', $params, time());
		
		// delete first
		$client->bulkDeleteDocuments();
		
		$update_actions = array(
			'no_index_ts',
			'update',
			'reindex',
		);
		foreach ($update_actions as $action) {
			
			$options = elasticsearch_get_bulk_options($action);
			if (empty($options)) {
				continue;
			}
			
			$getter = '';
			if ($action === 'no_index_ts') {
				$getter = 'elgg_get_entities';
			}
			$time_left = self::batchSync($options, $starttime, $getter);
			if ($time_left === false) {
				return;
			}
		}
	}
	
	/**
	 * Batch sync data to ElasticSearch
	 *
	 * This function is timed at a max runtime of 30sec
	 *
	 * @param array  $options   the options for elgg_get_entities()
	 * @param int    $crontime the starttime of the cron in order to limit max runtime
	 * @param string $getter    the getter function to use for \ElggBatch
	 *
	 * @return bool|void
	 */
	protected static function batchSync($options, $crontime, $getter = '') {
		
		if (empty($options) || !is_array($options)) {
			return;
		}
		
		if (empty($getter)) {
			$getter = 'elgg_get_entities_from_private_settings';
		}
		
		if (!is_callable($getter)) {
			return false;
		}
		
		$client = elasticsearch_get_client();
		if (empty($client)) {
			return;
		}
		
		$crontime = sanitise_int($crontime, false);
		if (empty($crontime)) {
			$crontime = time();
		}
		
		if ((time() - $crontime) >= 30) {
			return false;
		}
		
		set_time_limit(40);
		$ia = elgg_set_ignore_access(true);
		$time_left = true;
		$batch_size = 100;
		
		$options['callback'] = false;
		$options['limit'] = $batch_size;
		
		while ($time_left && ($rows = call_user_func($getter, $options))) {
			
			$guids = array();
			foreach ($rows as $row) {
				$guids[] = (int) $row->guid;
			}
			
			$result = $client->bulkIndexDocuments($guids);
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
				
				set_private_setting($guid, ELASTICSEARCH_INDEXED_NAME, time());
			}
			
			if ((time() - $crontime) >= 30) {
				$time_left = false;
				break;
			}
		}
		
		// restore access
		elgg_set_ignore_access($ia);
		
		return $time_left;
	}
	
	/**
	 * Listen to the daily cron the do some cleanup jobs
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function dailyCleanup($hook, $type, $resultvalue, $params) {
		
		// find documents in ES which don't exist in Elgg anymore
		self::cleanupElasticsearch();
		
		// find entities in Elgg which should be in ES but aren't
		
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
		
		// ignore Elgg access
		$ia = elgg_set_ignore_access(true);
		
		try {
			while ($result = $client->scroll($scroll_params)) {
				
				$search_result = new SearchResult($result);
				
				$elasticsearch_guids = $search_result->toGuids();
				if (empty($elasticsearch_guids)) {
					break;
				}
				
				$elgg_guids = elgg_get_entities([
					'guids' => $elasticsearch_guids,
					'limit' => false,
					'callback' => function ($row) {
						return (int) $row->guid;
					}
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
		} catch (\Exception $e) {
			elgg_log('Elasticsearch cleanup: ' . $e->getMessage(), 'ERROR');
		}
		
		// restore access
		elgg_set_ignore_access($ia);
		
		// clear scroll
		try {
			$client->clearScroll($scroll_params);
		} catch (\Exception $e) {
			// unable to clean
		}
	}
}
