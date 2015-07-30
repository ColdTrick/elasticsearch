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
		
		$setting = elgg_get_plugin_setting('sync', 'elasticsearch');
		if ($setting !== 'yes') {
			// sync not enabled
			return;
		}
		
		$client = elasticsearch_get_client();
		if (empty($client)) {
			return;
		}
		
		$starttime = (int) elgg_extract('time', $params, time());
		
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
		$batch = new \ElggBatch($getter, $options);
		$batch->setIncrementOffset(false);
		
		foreach ($batch as $entity) {
			
			if ($client->updateDocument($entity->getGUID())) {
				$entity->setPrivateSetting(ELASTICSEARCH_INDEXED_NAME, time());
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
}
