<?php

namespace ColdTrick\ElasticSearch;

use Elgg\Menu\MenuItems;

class SearchHooks {
	
	/**
	 * Check search params for unsupported options
	 *
	 * @param \Elgg\Hook $hook 'search:params', 'all'
	 *
	 * @return void|array
	 */
	public static function searchParams(\Elgg\Hook $hook) {
		
		$search_params = $hook->getValue();
		if (isset($search_params['_elasticsearch_supported'])) {
			return;
		}
		
		if (!self::handleSearch() || self::detectUnsupportedSearchParams($search_params)) {
			$search_params['_elasticsearch_supported'] = false;
			
			return $search_params;
		}
		
		$search_params['_elasticsearch_supported'] = true;
		
		self::transformSearchParamFields($search_params);
		self::transformSearchParamQueryInLivesearch($search_params);
		
		return $search_params;
	}
	
	/**
	 * Transform provided search fields to the correct elasticsearch fields
	 *
	 * @param array $search_params the search params
	 *
	 * @return void
	 */
	protected static function transformSearchParamFields(array &$search_params) {
		
		if (!isset($search_params['fields'])) {
			return;
		}
		
		$metadata_should_be_attribute = [
			'description',
			'name',
			'tags',
			'title',
			'username',
		];
		
		foreach ($search_params['fields'] as $type => $fields) {
			if ($type !== 'metadata') {
				continue;
			}
			
			if (!isset($search_params['fields']['attributes'])) {
				$search_params['fields']['attributes'] = [];
			}
			
			foreach ($fields as $index => $field) {
				if (!in_array($field, $metadata_should_be_attribute)) {
					continue;
				}
				
				$search_params['fields']['attributes'][] = $field;
				unset($search_params['fields']['metadata'][$index]);
				
				// add title alias for name
				// @see self::searchFieldsNameToTitle()
				if ($field === 'name') {
					$search_params['fields']['attributes'][] = 'title';
				}
			}
		}
	}
	
	/**
	 * Add wildcard to livesearch queries to find more content
	 *
	 * @param array $search_params the search params
	 *
	 * @return void
	 */
	protected static function transformSearchParamQueryInLivesearch(array &$search_params) {
		
		if (!elgg_in_context('livesearch') || !isset($search_params['query'])) {
			return;
		}
		
		$query = elgg_extract('query', $search_params);
		$query = filter_var($query, FILTER_SANITIZE_STRING);
		$query = trim($query);
		$query = rtrim($query, '*');
		
		$search_params['query'] = $query . '*';
	}
	
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
		if (elgg_extract('_elasticsearch_supported', $return) === false) {
			return;
		}
		
		$sort = elgg_extract('sort', $return, 'relevance');
		if ($sort === 'time_created' && !get_input('sort')) {
			// default sorting by Elgg is time_created
			$sort = 'relevance';
		}
		
		$return['sort'] = $sort;
		$return['order'] = elgg_extract('order', $return, 'desc');
		
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
		
		$ignored_metadata_names = [
			'tags',
		];
		
		$value = array_merge($defaults, $value);
		if (empty($value['metadata'])) {
			return;
		}
		
		// remove user profile tag fields
		$user_tags = self::getUserProfileTagsFields();
		if (!empty($user_tags)) {
			foreach ($value['metadata'] as $index => $metadata_name) {
				if (in_array($metadata_name, $ignored_metadata_names)) {
					continue;
				}
				
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
				if (in_array($metadata_name, $ignored_metadata_names)) {
					continue;
				}
				
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
	 * @param \Elgg\Hook $hook 'search:fields', 'all'
	 *
	 * @return void|array
	 */
	public static function searchFields(\Elgg\Hook $hook) {
		
		if (!self::handleSearch()) {
			return;
		}
		
		if ($hook->getParam('_elasticsearch_supported') === false) {
			return;
		}
		
		$value = (array) $hook->getValue();
		
		$defaults = [
			'attributes' => [],
			'metadata' => [],
		];
		
		$metadata_should_be_attribute = [
			'description',
			'name',
			'tags',
			'title',
			'username',
		];
		
		$value = array_merge($defaults, $value);
		
		foreach ($value['metadata'] as $index => $name) {
			if (!in_array($name, $metadata_should_be_attribute)) {
				continue;
			}
			
			$value['attributes'][] = $name;
			unset($value['metadata'][$index]);
		}
		
		$value['attributes'] = array_values(array_unique($value['attributes']));
		
		return $value;
	}
	
	/**
	 * When searching in the attribute name, move it to title according to the mapping
	 *
	 * @param \Elgg\Hook $hook 'search:fields', 'all'
	 *
	 * @return void|array
	 */
	public static function searchFieldsNameToTitle(\Elgg\Hook $hook) {
		
		if (!self::handleSearch()) {
			return;
		}
		
		$value = (array) $hook->getValue();
		if (!isset($value['attributes']) || !in_array('name', $value['attributes'])) {
			return;
		}
		
		
		foreach ($value['attributes'] as $index => $name) {
			if ($name !== 'name') {
				continue;
			}
			
			$value['attributes'][] = 'title';
			unset($value['attributes'][$index]);
			
			break;
		}
		
		$value['attributes'] = array_values(array_unique($value['attributes']));
		
		return $value;
	}
	
	/**
	 * Get all configured tag fields for users
	 *
	 * @return string[]
	 */
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
	
	/**
	 * Get all configured tag fields for groups
	 *
	 * @return string[]
	 */
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
		
		if (elgg_get_plugin_setting('search', 'elasticsearch') !== 'yes') {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Get The Elasticsearch client for searches
	 *
	 * @param array $params search params
	 *
	 * @return false|\ColdTrick\ElasticSearch\Client
	 */
	protected static function getClientForHooks($params) {
		
		if (!self::handleSearch()) {
			return false;
		}
		
		if (self::detectUnsupportedSearchParams($params)) {
			return false;
		}
		
		$client = elasticsearch_get_client();
		if (!$client) {
			return false;
		}
		
		$client->search_params->initializeSearchParams($params);
		$client->search_params->addEntityAccessFilter();
	
		return $client;
	}
	
	/**
	 * Check the search params for unsupported params
	 *
	 * @param array $params search params
	 *
	 * @return bool
	 */
	protected static function detectUnsupportedSearchParams(array $params) {
		
		$keys = [
			'metadata_name_value_pair',
			'metadata_name_value_pairs',
			'relationship',
			'relationship_guid',
		];
		
		foreach ($keys as $key) {
			if (!empty($params[$key])) {
				return true;
			}
		}
		
		return false;
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
				'href' => elgg_http_add_url_query_elements($url, ['sort' => $item, 'order' => null, 'offset' => null]),
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
					'href' => elgg_http_add_url_query_elements($url, ['sort' => 'member_count', 'order' => 'desc', 'offset' => null]),
					'parent_name' => 'sort',
					'selected' => ($current_sort === 'member_count'),
					'title' => $title
				]);
				break;
		}
		
		return $return;
	}
		
	/**
	 * Hook to add profile field filters to search
	 *
	 * @param \Elgg\Hook $hook 'search_params', 'elasticsearch'
	 *
	 * @return void|\ColdTrick\ElasticSearch\Client
	 */
	public static function filterProfileFields(\Elgg\Hook $hook) {
		
		$search_params = $hook->getParam('search_params');
		if (empty($search_params) || !is_array($search_params)) {
			return;
		}
		
		$type = elgg_extract('type', $search_params);
		if ($type !== 'user') {
			return;
		}
		
		$filter = elgg_extract('search_filter', $search_params, []);
		$profile_field_filter = elgg_extract('profile_fields', $filter, []);
		if (empty($profile_field_filter)) {
			return;
		}
		
		$queries = [];
		foreach ($profile_field_filter as $field_name => $value) {
			
			$value = strtolower($value);
			$value = str_replace('&amp;', '&', $value);
			$value = str_replace('\\', ' ', $value);
			$value = str_replace('/', ' ', $value);
			$value = trim($value);
			if (elgg_is_empty($value)) {
				continue;
			}
			
			$string_value = $value;
			
			$value = explode(' ', $value);
			$value = array_filter($value);
			$value = implode('* *', $value);
			
			$sub_query = [];
			$sub_query['nested']['path'] = 'metadata';
			
			$shoulds = [];
			$shoulds['bool']['should'][] = [
				'query_string' => [
					'default_field' => "metadata.{$field_name}",
					'query' => "*{$value}*",
					'default_operator' => 'AND',
				],
			];
			$shoulds['bool']['should'][] = [
				'query_string' => [
					'default_field' => "metadata.{$field_name}",
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
		
		$client = $hook->getValue();
		
		$client->search_params->addQuery($queries);
		
		return $client;
	}
		
	/**
	 * Hook to add profile field filters to search
	 *
	 * @param \Elgg\Hook $hook 'search_params', 'elasticsearch'
	 *
	 * @return void|Client
	 */
	public static function sortByGroupMembersCount(\Elgg\Hook $hook) {
		
		$search_params = $hook->getParam('search_params');
		
		$sort = elgg_extract('sort', $search_params);
		$order = elgg_extract('order', $search_params, 'desc');
		if ($sort !== 'member_count') {
			return;
		}
		
		$sort_config = [
			'order' => $order,
			'missing' => '_last',
			'unmapped_type' => 'long',
		];
		
		/* @var $return Client */
		$return = $hook->getValue();
		
		$return->search_params->addSort('counters.member_count', $sort_config);
		$return->search_params->addSort('_score');
		
		return $return;
	}
	
	/**
	 * Hook to transform a search result to an Elgg Entity
	 *
	 * @param \Elgg\Hook $hook 'to:entity', 'elasticsearch'
	 *
	 * @return void|false|\ElggEntity
	 */
	public static function sourceToEntity(\Elgg\Hook $hook) {
	
		$hit = $hook->getParam('hit');
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
			return entity_row_to_elggstar($row);
		} catch (\Exception $e) {
			elgg_log($e->getMessage(), 'NOTICE');
		}
	}
}
