<?php

namespace ColdTrick\ElasticSearch;

class Views {
	
	/**
	 * Display the search score in the search results
	 *
	 * @param \Elgg\Hook $hook 'view_vars', 'object/elements/imprint/contents'
	 *
	 * @return void|array
	 */
	public static function displaySearchScoreInImprint(\Elgg\Hook $hook) {
		
		if (!elgg_is_admin_logged_in() || elgg_get_plugin_setting('search_score', 'elasticsearch') !== 'yes') {
			return;
		}
		
		$result = $hook->getValue();
		
		$entity = elgg_extract('entity', $result);
		if (!$entity instanceof \ElggEntity || !$entity->getVolatileData('search_score')) {
			return;
		}
		
		$imprint = elgg_extract('imprint', $result, []);
		$imprint['elasticsearch_score'] = [
			'icon_name' => 'search',
			'content' => elgg_echo('elasticsearch:search_score', [$entity->getVolatileData('search_score')]),
		];
		
		$result['imprint'] = $imprint;
		
		return $result;
	}
}
