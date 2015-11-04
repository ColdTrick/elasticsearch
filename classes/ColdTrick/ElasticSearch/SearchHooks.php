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
	 * Hook to return search results for entity searches
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function searchEntities($hook, $type, $returnvalue, $params) {
		if (!empty($returnvalue)) {
			return;
		}
				
		$client = self::getClientForHooks($params);
		if (!$client) {
			return;
		}
				
		switch ($type) {
			case 'user':
				$client->search_params->setType('user');
				break;
			case 'group':
				$client->search_params->setType('group');
				break;
			case 'object':
				$subtype = elgg_extract('subtypes', $params, elgg_extract('subtype', $params, []));
		
				if (empty($subtype)) {
					return;
				}
				
				$subtype = (array) $subtype;
				
				array_walk($subtype, function(&$value) {
					$value = "object.{$value}";
				});
				
				$client->search_params->setType($subtype);
				break;
			case 'tags':
				$tag_query['bool']['must'][]['term']['tags'] = $params['query'];
				$client->search_params->setQuery($tag_query);
				break;
			case 'combined:all':
				// triggered by search advanced
				$type_subtypes = elgg_extract('type_subtype_pairs', $params);
				$types = [];
				foreach ($type_subtypes as $type => $subtypes) {
					if (empty($subtypes)) {
						$types[] = $type;
						continue;
					}
					
					foreach ($subtypes as $subtype) {
						$types[] = "{$type}.{$subtype}";
					}
				}
				
				$client->search_params->setType($types);
				
				break;
		}
		
		$client = elgg_trigger_plugin_hook('search_params', 'elasticsearch', ['search_params' => $params], $client);
		
		if ($params['count'] == true) {
			$result = $client->search_params->count();
		} else {
			$result = $client->search_params->execute();
		}
		
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
	
		$type_subtype_pairs = elasticsearch_get_registered_entity_types_for_search();
	
		$types = [];
		foreach ($type_subtype_pairs as $type => $subtypes) {
			if (empty($subtypes)) {
				$types[] = $type;
				continue;
			}
				
			foreach ($subtypes as $subtype) {
				$types[] = "{$type}.{$subtype}";
			}
		}
	
		$client->search_params->setType($types);
	
		$tag_query['bool']['must']['term']['tags'] = strtolower($params['query']);
	
		$client->search_params->setQuery($tag_query);
	
		$client = elgg_trigger_plugin_hook('search_params', 'elasticsearch', ['search_params' => $params], $client);
	
		if ($params['count'] == true) {
			$result = $client->search_params->count();
		} else {
			$result = $client->search_params->execute();
		}
	
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
	public static function prepareSearchParamsForHook(&$client, $params) {
		if (!$client) {
			return;
		}
		
		$client->search_params->setFrom(elgg_extract('offset', $params, 0));
		$client->search_params->setSize(elgg_extract('limit', $params, 10));
		
		$query = elgg_extract('query', $params);
		if (!empty($query)) {
			
			$elastic_query['bool']['should']['query_string'] = [
				'fields' => self::getQueryFields($params),
				'query' => $query,
				'default_operator' => 'AND',
			];
									
			$client->search_params->setQuery($elastic_query);
			if (!elgg_extract('count', $params, false)) {
				$client->search_params->setSuggestion($query);
			}
		}
		
		// container filter
		$container_guid = (int) elgg_extract('container_guid', $params);
		if (!empty($container_guid)) {
			$container_filter['bool']['must'][]['term']['container_guid'] = $container_guid;
			$client->search_params->addFilter($container_filter);
		}
		
		// sort & order
		$sort = elgg_extract('sort', $params);
		$order = elgg_extract('order', $params, 'desc');
		$sort_field = false;
		
		switch ($sort) {
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
		}
		
		if ($sort_field) {
			$client->search_params->addSort($sort_field, [
				'order' => $order,
				'ignore_unmapped' => true,
				'missing' => '_last',
			]);
		}
	}
	
	protected static function getQueryFields($params = []) {
		
		$default = [
			'title',
			'description',
			'tags',
		];
		return elgg_trigger_plugin_hook('query_fields', 'elasticsearch', ['search_params' => $params], $default);
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
		
		// sort parent menu
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
				'href' => elgg_http_add_url_query_elements($url, ['sort' => $item, 'order' => null, 'offset' => null]),
				'parent_name' => 'sort',
				'selected' => ($current_sort === $item),
				'title' => $title
			]);
		}
		
		$search_params = (array) elgg_extract('search_params', $params, []);
		$type = elgg_extract('type', $search_params);
		switch ($type) {
			case 'group':
				$returnvalue[] = \ElggMenuItem::factory([
					'name' => 'members_count',
					'text' => elgg_echo("elasticsearch:menu:search_list:sort:member_count"),
					'href' => elgg_http_add_url_query_elements($url, ['sort' => 'member_count', 'order' => 'desc', 'offset' => null]),
					'parent_name' => 'sort',
					'selected' => ($current_sort === 'member_count'),
					'title' => $title
				]);
				break;
		}
				
		return $returnvalue;
	}
		
	/**
	 * Hook to add profile field filters to search
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function filterProfileFields($hook, $type, $returnvalue, $params) {
		if (empty($params) || !is_array($params)) {
			return;
		}
		
		$search_params = elgg_extract('search_params', $params);
		if (empty($search_params) || !is_array($search_params)) {
			return;
		}
		
		$type = elgg_extract('type', $search_params);
		if ($type !== 'user') {
			return;
		}
		
		$filter = elgg_extract('search_filter', $search_params, []);
		$profile_field_filter = elgg_extract('profile_fields', $filter, []);
		$profile_field_soundex_filter = elgg_extract('profile_fields_soundex', $filter, []);

		if (empty($profile_field_filter)) {
			return;
		}
		
		$queries = [];
		foreach ($profile_field_filter as $field_name => $value) {
			if ($value === "") {
				continue;
			}
			$sub_query = [];
			$sub_query['nested']['path'] = 'metadata';
			$sub_query['nested']['query']['bool']['must'][] = [
				'term' => [
					'metadata.name' => $field_name,
				],
			];
			$sub_query['nested']['query']['bool']['must'][] = [
				'wildcard' => [
					'metadata.value' => "{$value}*",
				],
			];
			
			$queries['bool']['must'][] = $sub_query;
			
		}
		
		if (empty($queries)) {
			return;
		}
		
		$returnvalue->search_params->addQuery($queries);
		return $returnvalue;
	}
		
	/**
	 * Hook to add profile field queries to search (as configured in the Search Advanced plugin)
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function queryProfileFields($hook, $type, $returnvalue, $params) {
		
		if (empty($params) || !is_array($params)) {
			return;
		}
		
		$search_params = elgg_extract('search_params', $params);
		if (empty($search_params) || !is_array($search_params)) {
			return;
		}
				
		$profile_fields = array_keys(elgg_get_config('profile_fields'));
		if (empty($profile_fields)) {
			return;
		}
		
		$type = elgg_extract('type', $search_params);
		$type_subtype_pairs = elgg_extract('type_subtype_pairs', $search_params, []);
		if ($type !== 'user' && !array_key_exists('user', $type_subtype_pairs)) {
			return;
		}
		
		$field_names = [];
		foreach ($profile_fields as $key => $field) {
			$field_names[] = $field;
		}
		
		if (elgg_is_active_plugin('search_advanced')) {
			$profile_field_metadata_search_values = elgg_get_plugin_setting('user_profile_fields_metadata_search', 'search_advanced', []);
			if (!empty($profile_field_metadata_search_values)) {
				$profile_field_metadata_search_values = json_decode($profile_field_metadata_search_values, true);
				
				foreach ($field_names as $key => $field) {
					if (!in_array($field, $profile_field_metadata_search_values)) {
						continue;
					}
					
					unset($field_names[$key]);
				}
				
				$field_names = array_values($field_names);
			}
		}
		
		if (empty($field_names)) {
			return;
		}

		foreach ($field_names as $field) {
			$returnvalue[] = "profile.$field";
		}
		
		return $returnvalue;
	}
	
	/**
	 * Hook to add profile field filters to search
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param Client $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void|Client
	 */
	public static function sortByGroupMembersCount($hook, $type, $returnvalue, $params) {
		
		if (empty($params) || !is_array($params)) {
			return;
		}
		
		$search_params = elgg_extract('search_params', $params);
		if (empty($search_params) || !is_array($search_params)) {
			return;
		}
		
		$type = elgg_extract('type', $search_params);
		if ($type !== 'group') {
			return;
		}
		
		$sort = elgg_extract('sort', $search_params);
		$order = elgg_extract('order', $search_params, 'desc');
		if ($sort !== 'member_count') {
			return;
		}
		
		$sort_config = [
			'order' => $order,
			'missing' => 0,
		];
		$returnvalue->search_params->addSort('counters.member_count', $sort_config);
		$returnvalue->search_params->addSort('_score');
		
		return $returnvalue;
	}
	
	/**
	 * Hook to transform a search result to an Elgg Entity
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param Client $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void|Client
	 */
	public static function sourceToEntity($hook, $type, $returnvalue, $params) {
	
		$hit = elgg_extract('hit', $params);
		$index = elgg_extract('_index', $hit);
		
		$elgg_index = elasticsearch_get_setting('index');
		if ($index !== $elgg_index) {
			return;
		}
		
		$source = elgg_extract('_source', $hit);
	
		$row = new \stdClass();
		foreach ($source as $key => $value) {
			switch($key) {
				case 'subtype':
					// elastic stores the textual version of the subtype, entity_row_to_elggstar needs the int
					$row->$key = get_subtype_id($source['type'], $value);
					break;
				case 'last_action':
				case 'time_created':
				case 'time_updated':
					// convert the timestamps to unix timestamps
					$value = strtotime($value);
				default:
					$row->$key = $value;
					break;
			}
		}
	
		// enabled attribute is not stored in elasticsearch by default
		$row->enabled = 'yes';
	
		// specials types
		if ($row->type == 'user') {
			// makes sure all attributes are loaded to prevent a db call
			$external_attributes = \ElggUser::getExternalAttributes();
			foreach($external_attributes as $key => $value) {
				if (isset($row->$key)) {
					continue;
				}
	
				$row->$key = $value;
			}
		}
	
		try {
			$result = entity_row_to_elggstar($row);
		} catch (\Exception $e) {
			elgg_log($e->getMessage(), 'NOTICE');
			return false;
		}
	
		return $result;
	}
}
