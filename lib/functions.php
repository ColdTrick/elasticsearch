<?php
/**
 * All helper functions are bundled here
 */

/**
 * Get a working ElasticSearch client for further use
 *
 * @return false|ColdTrick\ElasticSearch\Client
 */
function elasticsearch_get_client() {
	static $client;
	
	if (!isset($client)) {
		$client = false;
		
		$host = elgg_get_plugin_setting('host', 'elasticsearch');
		if (!empty($host)) {
			$params = array();
			
			$hosts = explode(',', $host);
			array_walk($hosts, 'trim');
			
			$params['hosts'] = $hosts;
			
			$params['logging'] = true;
			$params['logObject'] = new ColdTrick\ElasticSearch\DatarootLogger('log');
			
			// @todo trigger hook for all params
			
			try {
				$client = new ColdTrick\ElasticSearch\Client($params);
			} catch (Exception $e) {
				elgg_log("Unable to create ElasticSearch client: {$e->getMessage()}", 'ERROR');
			}
		}
	}
	
	return $client;
}
