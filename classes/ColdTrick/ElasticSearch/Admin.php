<?php

namespace ColdTrick\ElasticSearch;

class Admin {

	/**
	 * Add menu items to the admin page menu
	 *
	 * @param string          $hook        the name of the hook
	 * @param string          $type        the type of the hook
	 * @param \ElggMenuItem[] $returnvalue current return value
	 * @param array           $params      supplied params
	 *
	 * @return void|\ElggMenuItem[]
	 */
	public static function pageMenu($hook, $type, $returnvalue, $params) {
		
		if (!elgg_in_context('admin') || !elgg_is_admin_logged_in()) {
			return;
		}
		
		// parent
		$returnvalue[] = \ElggMenuItem::factory(array(
			'name' => 'elasticsearch',
			'href' => '',
			'text' => elgg_echo('admin:elasticsearch'),
			'section' => 'administer',
		));
		
		$returnvalue[] = \ElggMenuItem::factory(array(
			'name' => 'elasticsearch:stats',
			'href' => 'admin/elasticsearch/statistics',
			'text' => elgg_echo('admin:elasticsearch:statistics'),
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		));
		
		$returnvalue[] = \ElggMenuItem::factory(array(
			'name' => 'elasticsearch:search',
			'href' => 'admin/elasticsearch/search',
			'text' => elgg_echo('admin:elasticsearch:search'),
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		));
		
		$returnvalue[] = \ElggMenuItem::factory(array(
			'name' => 'elasticsearch:settings',
			'href' => 'admin/plugin_settings/elasticsearch',
			'text' => elgg_echo('settings'),
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		));
		
		return $returnvalue;
	}
}
