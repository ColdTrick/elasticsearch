<?php

namespace ColdTrick\ElasticSearch;

class SearchHooks {
	
	/**
	 * Hook to fallback to default search functions
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function searchFallback($hook, $type, $returnvalue, $params) {
		if (!empty($returnvalue)) {
			return;
		}
		
		if (!in_array($type, ['object', 'user', 'group', 'tags'])) {
			return;
		}
		
		switch ($type) {
			case 'object':
				return search_objects_hook($hook, $type, $returnvalue, $params);
			case 'user':
				return search_users_hook($hook, $type, $returnvalue, $params);
			case 'group':
				return search_groups_hook($hook, $type, $returnvalue, $params);
			case 'tags':
				return search_tags_hook($hook, $type, $returnvalue, $params);
		}
	}

	/**
	 * Hook to return search results for user entity types
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function searchUsers($hook, $type, $returnvalue, $params) {
		if (!empty($returnvalue)) {
			return;
		}
				
		$client = self::getClientForHooks($params);
		if (!$client) {
			return;
		}
		
		$client->search_params->setType('user');
		
		$result = $client->search_params->execute();
		
		return self::transformSearchResults($result, $params);
	}

	/**
	 * Hook to return search results for object entity types
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function searchObjects($hook, $type, $returnvalue, $params) {
		if (!empty($returnvalue)) {
			return;
		}
		
		$client = self::getClientForHooks($params);
		if (!$client) {
			return;
		}
		
		$subtype = elgg_extract('subtypes', $params, elgg_extract('subtype', $params, []));
		
		if (empty($subtype)) {
			return;
		}
		
		$subtype = (array) $subtype;
		
		array_walk($subtype, function(&$value) {
			$value = "object.{$value}";
		});
		
		$client->search_params->setType($subtype);
		
		$client = elgg_trigger_plugin_hook('search_params', 'elasticsearch', ['search_params' => $params], $client);
		
		$result = $client->search_params->execute();
		
		return self::transformSearchResults($result, $params);
	}
	
	/**
	 * Hook to return search results for group entity types
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function searchGroups($hook, $type, $returnvalue, $params) {
		if (!empty($returnvalue)) {
			return;
		}
		
		$client = self::getClientForHooks($params);
		if (!$client) {
			return;
		}
		
		$client->search_params->setType('group');
		
		$client = elgg_trigger_plugin_hook('search_params', 'elasticsearch', ['search_params' => $params], $client);
				
		$result = $client->search_params->execute();
		
		return self::transformSearchResults($result, $params);
	}
	
	/**
	 * Hook to return a search for content with a give tag (or tags)
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function searchTags($hook, $type, $returnvalue, $params) {
		if (!empty($returnvalue)) {
			return;
		}
		
		$client = self::getClientForHooks($params);
		if (!$client) {
			return;
		}
		
		$tag_query['bool']['must']['term']['tags'] = $params['query'];
		$client->search_params->setQuery($tag_query);
		
		$client = elgg_trigger_plugin_hook('search_params', 'elasticsearch', ['search_params' => $params], $client);
				
		$result = $client->search_params->execute();
		
		return self::transformSearchResults($result, $params);
	}

	protected static function getClientForHooks($params) {
	
		if (elasticsearch_get_setting('search') !== 'yes') {
			return false;
		}
	
		$client = elasticsearch_get_client();
		if (!$client) {
			return false;
		}
	
		self::prepareSearchParamsForHook($client, $params);
	
		$client->search_params->addEntityAccessFilter();
	
		return $client;
	}
	
	/**
	 * Transforms search result into hooks result array
	 *
	 * @param \ColdTrick\ElasticSearch\SearchResult $result the elastic search results
	 *
	 * @return array
	 */
	protected static function transformSearchResults($result, $hook_params) {
		if (empty($result)) {
			return ['count' => 0, 'entities' => null];
		}
		
		return [
			'count' => $result->getCount(),
			'entities' => $result->toEntities($hook_params),
		];
	}
	
	
	/**
	 * Prepares client search params based on hook params
	 *
	 * @param \ColdTrick\ElasticSearch\Client $client search client
	 * @param array                           $params hook params
	 *
	 * @return void
	 */
	protected static function prepareSearchParamsForHook(&$client, $params) {
		if (!$client) {
			return;
		}
		
		$client->search_params->setFrom(elgg_extract('offset', $params, 0));
		$client->search_params->setSize(elgg_extract('limit', $params, 10));
		
		$query = elgg_extract('query', $params);
		if (!empty($query)) {
			$elastic_query['bool']['must']['match']['_all'] = $query;
			$client->search_params->setQuery($elastic_query);
		}
		
		// sort & order
		$order = elgg_extract('order', $params, 'desc');
		$sort_field = false;
		
		switch ($params['sort']) {
			case 'newest':
				$sort_field = 'time_created';
				$order = 'desc';
				break;
			case 'oldest':
				$sort_field = 'time_created';
				$order = 'asc';
				break;
			case 'alpha_az':
				$sort_field = 'title.raw';
				$order = 'asc';
				break;
			case 'alpha_za':
				$sort_field = 'title.raw';
				$order = 'desc';
				break;
			case 'alpha':
				$sort_field = 'title.raw';
				break;
			default:
				break;
		}
		
		if ($sort_field) {
			$client->search_params->addSort($sort_field, [
				'order' => $order,
				'ignore_unmapped' => true,
				'missing' => '_last',
			]);
		}
	 		
// 	 		// suggestion
// 	 		if ($client->getSuggestions() == null) {
// 	 			$result['body']['suggest']['text'] = $query;
// 	 			$result['body']['suggest']['suggestions']['phrase'] = [
// 		 			"field" => "_all",
// 		 			"max_errors" => 2,
// 		 			"size" => 1,
// 		 			"real_word_error_likelihood" => 0.95,
// 		 			"gram_size" => 1,
// 		 			"direct_generator" => [[
// 			 			"field" => "_all",
// 			 			"suggest_mode" => "popular",
// 			 			"min_word_length" => 1
// 		 			]]
// 	 			];
// 	 		}
//
	}
		
	/**
	 * Hook to add items to the search_list menu
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function registerSortMenu($hook, $type, $returnvalue, $params) {
		if (elasticsearch_get_setting('search') !== 'yes') {
			return;
		}
		$title = elgg_echo('elasticsearch:menu:search_list:sort:title');
		$url = current_page_url();
		
		$current_sort = get_input('sort', 'relevance');
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'sort',
			'text' => elgg_view_icon('eye'),
			'href' => '#',
			'title' => $title
		]);
		
		$items = ['relevance', 'alpha_az', 'alpha_za', 'newest', 'oldest'];
		
		foreach ($items as $item) {
			$returnvalue[] = \ElggMenuItem::factory([
				'name' => $item,
				'text' => elgg_echo("elasticsearch:menu:search_list:sort:{$item}"),
				'href' => elgg_http_add_url_query_elements($url, ['sort' => $item, 'order' => null]),
				'parent_name' => 'sort',
				'selected' => ($current_sort === $item),
				'title' => $title
			]);
		}
				
		return $returnvalue;
	}
}
