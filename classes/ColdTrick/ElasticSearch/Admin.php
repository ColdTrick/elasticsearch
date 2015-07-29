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
		
		$returnvalue[] = \ElggMenuItem::factory(array(
			'name' => 'elasticsearch:stats',
			'href' => 'admin/statistics/elasticsearch',
			'text' => elgg_echo('admin:statistics:elasticsearch'),
			'parent_name' => 'statistics',
			'section' => 'administer',
		));
		
		return $returnvalue;
	}
}
