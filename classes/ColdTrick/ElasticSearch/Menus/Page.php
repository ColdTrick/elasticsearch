<?php

namespace ColdTrick\ElasticSearch\Menus;

class Page {

	/**
	 * Add menu items to the admin page menu
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:page'
	 *
	 * @return void|\ElggMenuItem[]
	 */
	public static function admin(\Elgg\Hook $hook) {
		
		if (!elgg_in_context('admin') || !elgg_is_admin_logged_in()) {
			return;
		}
		
		$returnvalue = $hook->getValue();
		
		// parent
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'elasticsearch',
			'href' => false,
			'text' => elgg_echo('admin:elasticsearch'),
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'elasticsearch:stats',
			'href' => 'admin/elasticsearch/statistics',
			'text' => elgg_echo('admin:elasticsearch:statistics'),
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'elasticsearch:indices',
			'href' => 'admin/elasticsearch/indices',
			'text' => elgg_echo('admin:elasticsearch:indices'),
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'elasticsearch:search',
			'href' => 'admin/elasticsearch/search',
			'text' => elgg_echo('admin:elasticsearch:search'),
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'elasticsearch:settings',
			'href' => 'admin/plugin_settings/elasticsearch',
			'text' => elgg_echo('settings'),
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'elasticsearch:inspect',
			'href' => 'admin/elasticsearch/inspect',
			'text' => elgg_echo('admin:elasticsearch:inspect'),
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		]);
		
		return $returnvalue;
	}
}
