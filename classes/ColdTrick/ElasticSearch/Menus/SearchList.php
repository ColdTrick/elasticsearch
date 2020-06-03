<?php

namespace ColdTrick\ElasticSearch\Menus;

use Elgg\Menu\MenuItems;

/**
 * Add items to the 'search_list' menu
 */
class SearchList {
	
	/**
	 * Hook to add items to the search_list menu
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:search_list'
	 *
	 * @return MenuItems
	 */
	public static function registerSortMenu(\Elgg\Hook $hook) {
		
		if (elgg_get_plugin_setting('search', 'elasticsearch') !== 'yes') {
			return;
		}
		
		$title = elgg_echo('elasticsearch:menu:search_list:sort:title');
		$url = current_page_url();
		
		$current_sort = get_input('sort', 'relevance');
		
		$return = $hook->getValue();
		
		// sort parent menu
		$return[] = \ElggMenuItem::factory([
			'name' => 'sort',
			'text' => elgg_view_icon('eye'),
			'href' => false,
			'title' => $title
		]);
		
		$items = ['relevance', 'alpha_az', 'alpha_za', 'newest', 'oldest'];
		foreach ($items as $item) {
			$return[] = \ElggMenuItem::factory([
				'name' => $item,
				'text' => elgg_echo("elasticsearch:menu:search_list:sort:{$item}"),
				'href' => elgg_http_add_url_query_elements($url, [
					'sort' => $item,
					'order' => null,
					'offset' => null,
				]),
				'parent_name' => 'sort',
				'selected' => ($current_sort === $item),
				'title' => $title
			]);
		}
		
		$search_params = (array) $hook->getParam('search_params', []);
		$type = elgg_extract('type', $search_params);
		switch ($type) {
			case 'group':
				$return[] = \ElggMenuItem::factory([
					'name' => 'members_count',
					'text' => elgg_echo("elasticsearch:menu:search_list:sort:member_count"),
					'href' => elgg_http_add_url_query_elements($url, [
						'sort' => 'member_count',
						'order' => 'desc',
						'offset' => null,
					]),
					'parent_name' => 'sort',
					'selected' => ($current_sort === 'member_count'),
					'title' => $title
				]);
				break;
		}
		
		return $return;
	}
}
