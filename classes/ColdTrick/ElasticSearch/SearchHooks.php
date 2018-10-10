<?php

namespace ColdTrick\ElasticSearch;

class SearchHooks {
	
	/**
	 * Set some search options before doing actual search
	 *
	 * @param \Elgg\Hook $hook 'search:options', 'all'
	 *
	 * @return void|array
	 */
	public static function searchOptions(\Elgg\Hook $hook) {
		
		if (!self::handleSearch()) {
			return;
		}
		
		$return = $hook->getValue();
		
		$sort = get_input('sort');
		switch ($sort) {
			case 'time_created':
				// Elgg defaults to time_created
				break;
			default:
				$return['sort'] = 'relevance';
				$return['order'] = get_input('order', 'desc');
				break;
		}
		
		return $return;
	}
	
	/**
	 * Change object search fields
	 *
	 * @param \Elgg\Hook $hook 'search:fields', 'object'
	 *
	 * @return void|array
	 */
	public static function objectSearchFields(\Elgg\Hook $hook) {
		
		if (!self::handleSearch()) {
			return;
		}
		
		$value = (array) $hook->getValue();
		
		$defaults = [
			'metadata' => [],
		];
		
		$value = array_merge($defaults, $value);
		if (empty($value['metadata'])) {
			return;
		}
		
		// remove user profile tag fields
		$user_tags = self::getUserProfileTagsFields();
		if (!empty($user_tags)) {
			foreach ($value['metadata'] as $index => $metadata_name) {
				if (!in_array($metadata_name, $user_tags)) {
					continue;
				}
				
				unset($value['metadata'][$index]);
			}
		}
		
		// remove group profile tag fields
		$group_tags = self::getGroupProfileTagsFields();
		if (!empty($group_tags)) {
			foreach ($value['metadata'] as $index => $metadata_name) {
				if (!in_array($metadata_name, $group_tags)) {
					continue;
				}
				
				unset($value['metadata'][$index]);
			}
		}
		
		return $value;
	}
	
	/**
	 * Change group search fields
	 *
	 * @param \Elgg\Hook $hook 'search:fields', 'group'
	 *
	 * @return void|array
	 */
	public static function groupSearchFields(\Elgg\Hook $hook) {
		
		if (!self::handleSearch()) {
			return;
		}
		
		// remove user profile tag fields (not present in group profile fields)
		$value = (array) $hook->getValue();
		
		$defaults = [
			'metadata' => [],
		];
		
		$value = array_merge($defaults, $value);
		if (empty($value['metadata'])) {
			return;
		}
		
		$user_tags = self::getUserProfileTagsFields();
		if (empty($user_tags)) {
			return;
		}
		
		$group_fields = elgg_get_config('group', []);
		foreach ($value['metadata'] as $index => $metadata_name) {
			if (isset($group_fields[$metadata_name]) || !in_array($metadata_name, $user_tags)) {
				continue;
			}
			
			unset($value['metadata'][$index]);
		}
		
		return $value;
	}
	
	/**
	 * Change user search fields
	 *
	 * @param \Elgg\Hook $hook 'search:fields', 'user'
	 *
	 * @return void|array
	 */
	public static function userSearchFields(\Elgg\Hook $hook) {
		
		if (!self::handleSearch()) {
			return;
		}
		
		// remove profile tag fields from metadata
		$value = (array) $hook->getValue();
		
		$defaults = [
			'metadata' => [],
		];
		
		$value = array_merge($defaults, $value);
		if (empty($value['metadata'])) {
			return;
		}
		
		$group_tags = self::getGroupProfileTagsFields();
		$user_tags = self::getUserProfileTagsFields();
		foreach ($value['metadata'] as $index => $metadata_name) {
			$unset = false;
			if (in_array($metadata_name, $user_tags) ) {
				$unset = true;
			} elseif (in_array($metadata_name, $group_tags)) {
				$unset = true;
			}
			
			if (!$unset) {
				continue;
			}
			
			unset($value['metadata'][$index]);
		}
		
		return $value;
	}
	
	/**
	 * Move some search fields around
	 *
	 * @param \Elgg\Hook $hook 'search:fields', 'entities'
	 *
	 * @return void|array
	 */
	public static function searchFields(\Elgg\Hook $hook) {
		
		if (!self::handleSearch()) {
			return;
		}
		
		$value = (array) $hook->getValue();
		
		$defaults = [
			'metadata' => [],
		];
		
		$value = array_merge($defaults, $value);
		if (in_array('tags', $value['metadata'])) {
			return;
		}
		
		$value['metadata'][] = 'tags';
		
		return $value;
	}
	
	protected static function getUserProfileTagsFields() {
		
		$fields = elgg_get_config('profile_fields');
		if (empty($fields)) {
			return [];
		}
		
		$result = [];
		foreach ($fields as $metadata_name => $type) {
			if (!in_array($type, ['tags', 'tag', 'location'])) {
				continue;
			}
			
			$result[] = $metadata_name;
		}
		
		return $result;
	}
	
	protected static function getGroupProfileTagsFields() {
		
		$fields = elgg_get_config('group');
		if (empty($fields)) {
			return [];
		}
		
		$result = [];
		foreach ($fields as $metadata_name => $type) {
			if ($type !== 'tags') {
				continue;
			}
			
			$result[] = $metadata_name;
		}
		
		return $result;
	}
	
	/**
	 * Hook to return search results for entity searches
	 *
	 * @param \Elgg\Hook $hook 'search:result', 'entities'
	 *
	 * @return void|\ElggEntity[]|int
	 * @throws \InvalidParameterException
	 */
	public static function searchEntities(\Elgg\Hook $hook) {
		
		$value = $hook->getValue();
		if (isset($value)) {
			return;
		}
		
		$params = $hook->getParams();
		
		$client = self::getClientForHooks($params);
		if (!$client) {
			return;
		}
		
		$new_params = $client->search_params->normalizeTypeSubtypeOptions($params);
		$type_subtype_pairs = elgg_extract('type_subtype_pairs', $new_params);
		if (!empty($type_subtype_pairs)) {
			foreach ($type_subtype_pairs as $type => $subtypes) {
				
				if (!empty($subtypes)) {
					foreach ($subtypes as $subtype) {
						$client->search_params->addType("{$type}.{$subtype}");
					}
				} else {
					$client->search_params->addType("{$type}.{$type}");
				}
			}
		}
		
		$client = elgg_trigger_plugin_hook('search_params', 'elasticsearch', ['search_params' => $params], $client);
		if (!$client instanceof Client) {
			throw new \InvalidParameterException('The return value of the search_params elasticsearch hook should return an instanceof \ColdTrick\Elasticsearch\Client');
		}
		
		if (elgg_extract('count', $params) == true) {
			$result = $client->search_params->count();
		} else {
			$result = $client->search_params->execute();
		}
		
		return self::transformSearchResults($result, $params);
	}

	/**
	 * Is this plugin doing the actual search
	 *
	 * @return bool
	 */
	protected static function handleSearch() {
		
		if (elgg_in_context('livesearch')) {
			return false;
		}
		
		if (elgg_get_plugin_setting('search', 'elasticsearch') !== 'yes') {
			return false;
		}
		
		return true;
	}
	
	protected static function getClientForHooks($params) {
	
		if (!self::handleSearch()) {
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
	 * @param \ColdTrick\ElasticSearch\SearchResult $result      the elastic search results
	 * @param array                                 $hook_params the search hook params
	 *
	 * @return array|int
	 */
	protected static function transformSearchResults($result, $hook_params) {
		
		if (elgg_extract('count', $hook_params) === true) {
			return $result->getCount();
		}
		
		return $result->toEntities($hook_params);
	}
	
	
	/**
	 * Prepares client search params based on hook params
	 *
	 * @param \ColdTrick\ElasticSearch\Client $client search client
	 * @param array                           $params hook params
	 *
	 * @return void
	 */
	public static function prepareSearchParamsForHook(Client &$client, $params) {
		
		if (!$client instanceof Client) {
			return;
		}
		
		$client->search_params->setFrom(elgg_extract('offset', $params, 0));
		$client->search_params->setSize(elgg_extract('limit', $params, elgg_get_config('default_limit')));
		
		$query = elgg_extract('query', $params);
		if (!empty($query)) {
		
			if (stristr($query, ' ')) {
				// also include a full sentence as part of the search query
				$query .= ' || "' . $query . '"';
			}
			
			$elastic_query = [];
						
			$elastic_query['bool']['must'][]['simple_query_string'] = [
				'fields' => self::getQueryFields($params),
				'query' => $query,
				'default_operator' => 'AND',
			];
												
			if (!elgg_extract('count', $params, false)) {
				$client->search_params->setSuggestion($query);
				$client->search_params->setHighlight(self::getDefaultHighlightParams($query));
			}
			
			$client->search_params->setQuery($elastic_query);
		}
		
		// container filter
		$container_guid = (int) elgg_extract('container_guid', $params);
		if (!empty($container_guid)) {
			$container_filter = [];
			$container_filter['bool']['must'][]['term']['container_guid'] = $container_guid;
			$client->search_params->addFilter($container_filter);
		}
		
		// owner filter
		$owner_guid = (int) elgg_extract('owner_guid', $params);
		if (!empty($owner_guid)) {
			$owner_filter = [];
			$owner_filter['bool']['must'][]['term']['owner_guid'] = $owner_guid;
			$client->search_params->addFilter($owner_filter);
		}
		
		// sort & order
		$sort = elgg_extract('sort', $params, 'relevance');
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
			case 'relevance':
				// if there is no specific sorting requested, sort by score
				// in case of identical score, sort by time created (newest first)
			
				$client->search_params->addSort('_score', []);
				$sort_field = 'time_created';
				$sort = 'desc';
				break;
		}
		
		if (!empty($sort_field)) {
			$client->search_params->addSort($sort_field, [
				'order' => $order,
				'ignore_unmapped' => true,
				'missing' => '_last',
			]);
		}
	}
	
	protected static function getDefaultHighlightParams($query) {
		$result = [];
		
		// global settings
		$result['encoder'] = 'html';
		$result['pre_tags'] = ['<strong class="search-highlight search-highlight-color1">'];
		$result['post_tags'] = ['</strong>'];
		$result['number_of_fragments'] = 3;
		$result['fragment_size'] = 100;
		$result['type'] = 'plain';
		
		// title
		$title_query['bool']['must']['match']['title']['query'] = $query;
		$result['fields']['title'] = [
			'number_of_fragments' => 0,
			'highlight_query' => $title_query,
		];
		
		// description
		$description_query['bool']['must']['match']['description']['query'] = $query;
		$result['fields']['description'] = [
			'highlight_query' => $description_query,
		];
		
		// tags
		$tags_query['bool']['must']['match']['tags']['query'] = $query;
		$result['fields']['tags'] = [
			'number_of_fragments' => 0,
			'highlight_query' => $tags_query,
		];
		
		return $result;
	}
	
	protected static function getQueryFields($params = []) {
		
		$result = [];
		
		$search_fields = elgg_extract('fields', $params, []);
		foreach ($search_fields as $type => $names) {
			if (empty($names)) {
				continue;
			}
			
			$names = array_unique($names);
			
			foreach ($names as $name) {
				switch ($type) {
					case 'attributes':
						$result[] = $name;
						break;
					case 'metadata':
						$result[] = "{$type}.{$name}";
						break;
					case 'annotations':
					case 'private_settings':
						// no yet supported
						break;
				}
			}
		}
		
		return $result;
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
		
		if (elgg_get_plugin_setting('search', 'elasticsearch') !== 'yes') {
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
// 		$profile_field_soundex_filter = elgg_extract('profile_fields_soundex', $filter, []);
		
		if (empty($profile_field_filter)) {
			return;
		}
		
		$queries = [];
		foreach ($profile_field_filter as $field_name => $value) {
			if ($value === "") {
				continue;
			}
			$value = strtolower($value);
			$value = str_replace('&amp;', '&', $value);
			$value = str_replace('\\', ' ', $value);
			$value = str_replace('/', ' ', $value);
			
			$string_value = $value;
			
			$value = explode(' ', $value);
			$value = array_filter($value);
			$value = implode('* *', $value);
			
			$sub_query = [];
			$sub_query['nested']['path'] = 'metadata';
			$sub_query['nested']['query']['bool']['must'][] = [
				'term' => [
					'metadata.name' => $field_name,
				],
			];
			$shoulds = [];
			$shoulds['bool']['should'][] = [
				'query_string' => [
					'default_field' => 'metadata.value',
					'query' => "*{$value}*",
					'default_operator' => 'AND',
				],
			];
			$shoulds['bool']['should'][] = [
				'query_string' => [
					'default_field' => 'metadata.value',
					'query' => "'{$string_value}'",
					'default_operator' => 'AND',
				],
			];
			
			$sub_query['nested']['query']['bool']['must'][] = $shoulds;
			
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
		
		$elgg_index = elgg_get_plugin_setting('index', 'elasticsearch');
		if ($index !== $elgg_index) {
			return;
		}
		
		$source = elgg_extract('_source', $hit);
	
		$row = new \stdClass();
		foreach ($source as $key => $value) {
			switch($key) {
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
	
		try {
			$result = entity_row_to_elggstar($row);
		} catch (\Exception $e) {
			elgg_log($e->getMessage(), 'NOTICE');
			return;
		}
	
		return $result;
	}
	

	/**
	 * Convert type/subtype array to an array of normalized types
	 *
	 * @param array $type_subtypes Array of types with their associated subtypes
	 *
	 * @return array
	 */
	public static function entityTypeSubtypesToSearchTypes($type_subtypes) {
		$result = [];
		foreach ($type_subtypes as $type => $subtypes) {
			if (empty($subtypes)) {
				$result[] = $type;
				continue;
			}
			
			foreach ($subtypes as $subtype) {
				$result[] = "{$type}.{$subtype}";
			}
		}
		
		return $result;
	}
}
