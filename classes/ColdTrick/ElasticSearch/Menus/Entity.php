<?php

namespace ColdTrick\ElasticSearch\Menus;

use Elgg\Menu\MenuItems;

/**
 * Add items to the 'entity' menu
 */
class Entity {
	
	/**
	 * Add an inspect menu item
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:entity'
	 *
	 * @return void|MenuItems
	 */
	public static function inspect(\Elgg\Hook $hook) {
		
		if (!elgg_is_admin_logged_in()) {
			return;
		}
		
		$entity = $hook->getEntityParam();
		if (!$entity instanceof \ElggEntity || $entity->getPrivateSetting(ELASTICSEARCH_INDEXED_NAME) === null) {
			// no entity (??) or not indexed
			return;
		}
		
		/* @var $result MenuItems */
		$result = $hook->getValue();
		
		$result[] = \ElggMenuItem::factory([
			'name' => 'elasticsearch_inspect',
			'icon' => 'search',
			'text' => elgg_echo('elasticsearch:menu:entity:inspect'),
			'href' => elgg_http_add_url_query_elements('admin/elasticsearch/inspect', [
				'guid' => $entity->guid,
			]),
		]);
		
		return $result;
	}
}
