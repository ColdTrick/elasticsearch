<?php

namespace ColdTrick\ElasticSearch\Menus;

use Elgg\Menu\MenuItems;

class Page {

	/**
	 * Add menu items to the admin page menu
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:page'
	 *
	 * @return void|MenuItems
	 */
	public static function admin(\Elgg\Hook $hook) {
		
		if (!elgg_in_context('admin') || !elgg_is_admin_logged_in()) {
			return;
		}
		
		$current_path = parse_url(current_page_url(), PHP_URL_PATH);
		$site_path = parse_url(elgg_get_site_url(), PHP_URL_PATH);
		$parsed_path = substr($current_path, strlen($site_path));
		
		/* @var $returnvalue MenuItems */
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
			'text' => elgg_echo('admin:elasticsearch:statistics'),
			'href' => 'admin/elasticsearch/statistics',
			'selected' => stristr($parsed_path, 'admin/elasticsearch/statistics') !== false,
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'elasticsearch:indices',
			'text' => elgg_echo('admin:elasticsearch:indices'),
			'href' => 'admin/elasticsearch/indices',
			'selected' => stristr($parsed_path, 'admin/elasticsearch/indices') !== false,
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'elasticsearch:search',
			'text' => elgg_echo('admin:elasticsearch:search'),
			'href' => 'admin/elasticsearch/search',
			'selected' => stristr($parsed_path, 'admin/elasticsearch/search') !== false,
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'elasticsearch:settings',
			'text' => elgg_echo('settings'),
			'href' => 'admin/plugin_settings/elasticsearch',
			'selected' => stristr($parsed_path, 'admin/plugin_settings/elasticsearch') !== false,
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'elasticsearch:inspect',
			'text' => elgg_echo('admin:elasticsearch:inspect'),
			'href' => 'admin/elasticsearch/inspect',
			'selected' => stristr($parsed_path, 'admin/elasticsearch/inspect') !== false,
			'parent_name' => 'elasticsearch',
			'section' => 'administer',
		]);
		
		return $returnvalue;
	}
}
