<?php

namespace ColdTrick\ElasticSearch;

class Search {
	
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
		
		if (!in_array($type, ['object', 'user', 'group'])) {
			return;
		}
		
		switch ($type) {
			case 'object':
				return search_objects_hook($hook, $type, $returnvalue, $params);
			case 'user':
				return search_users_hook($hook, $type, $returnvalue, $params);
			case 'group':
				return search_groups_hook($hook, $type, $returnvalue, $params);
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
		
		if (elasticsearch_get_setting('search') !== 'yes') {
			return;
		}
		
		$search_params = self::getDefaultSearchParamsForHook($params);
		$search_params['type'] = 'user';
		
		return self::performSearch($search_params);
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
		
		if (elasticsearch_get_setting('search') !== 'yes') {
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
		
		$search_params = self::getDefaultSearchParamsForHook($params);
		$search_params['type'] = $subtype;
		
		return self::performSearch($search_params);
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
		
		if (elasticsearch_get_setting('search') !== 'yes') {
			return;
		}
		
		$search_params = self::getDefaultSearchParamsForHook($params);
		$search_params['type'] = 'group';
		
		return self::performSearch($search_params);
	}
	
	/**
	 * Hook to return a search for all content
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function searchAll($hook, $type, $returnvalue, $params) {
		if (!empty($returnvalue)) {
			return;
		}
		
		if (elasticsearch_get_setting('search') !== 'yes') {
			return;
		}
		
		$search_params = self::getDefaultSearchParamsForHook($params);
		
		return self::performSearch($search_params);
	}
	
	/**
	 * Hook to adjust the custom search types
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function getTypes($hook, $type, $returnvalue, $params) {
		if (!is_array($returnvalue)) {
			return;
		}
		
		if (elasticsearch_get_setting('search') !== 'yes') {
			return;
		}
		
		$tags_key = array_search('tags', $returnvalue);
		if ($tags_key === false) {
			return;
		}
		
		unset($returnvalue[$tags_key]);
		return $returnvalue;
	}
	
	protected static function performSearch($params = []) {
		$client = elasticsearch_get_client();
		
		$params = self::prepareSearchParams($params);
		
		$search_result = $client->search($params);
		
		$hits = elgg_extract('hits', $search_result);
		$count = elgg_extract('total', $hits);
		
		$result_hits = elgg_extract('hits', $hits);
		
		$result = ['count' => $count, 'hits' => $result_hits];
		
		$result = self::transformHits($result, $params);
		
		$suggest = elgg_extract('suggest', $search_result);
		if (!empty($suggest)) {
			$client->setSuggestions($suggest);
		}
		
		return $result;
	}
	
	protected static function transformHits($result, $params) {
		
		$count = elgg_extract('count', $result);
		$hits = elgg_extract('hits', $result);
		
		if (empty($count) || empty($hits)) {
			return $result;
		}
		
		// transform results
		$entities = [];
		foreach ($hits as $hit) {
			// @todo check if elgg index is used
			$body = elgg_extract('_source', $hit);

			$entity = self::resultBodyToEntity($body);
			
			if (!$entity) {
				continue;
			}
			
			// set correct search highlighting
			$query = $params['body']['query']['bool']['must']['term']['_all'];
			
			$title = elgg_extract('title', $body, elgg_extract('name', $body));
			$title = search_get_highlighted_relevant_substrings($title, $query);
			$entity->setVolatileData('search_matched_title', $title);
				
			$desc = elgg_extract('description', $body);
			$desc = search_get_highlighted_relevant_substrings($desc, $query);
			$entity->setVolatileData('search_matched_description', $desc);
				
			$entities[] = $entity;
		}
		
		return ['count' => $count, 'entities' => $entities];
	}
	
	/**
	 * Transforms a hit result body to an ElggEntity
	 *
	 * @param array $body
	 *
	 * @return \ElggEntity|false
	 */
	protected static function resultBodyToEntity($body) {
		
		$row = new \stdClass();
		foreach ($body as $key => $value) {
			switch($key) {
				case 'subtype':
					// elastic stores the textual version of the subtype, entity_row_to_elggstar needs the int
					$row->$key = get_subtype_id($body['type'], $value);
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
	
	protected static function getDefaultSearchParamsForHook($params) {
		$client = elasticsearch_get_client();
		
		if (!$client) {
			return [];
		}
				
		$result = [];
		$result['from'] = $params['offset'];
		$result['size'] = $params['limit'];
		
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
			default:
			case 'alpha_za':
					$sort_field = 'title.raw';
					$order = 'desc';
				break;
			default:
				break;
		}
		
		if ($sort_field) {
			$result['body']['sort'][$sort_field] = [
				'order' => $order,
				'ignore_unmapped' => true,
				'missing' => '_last',
			];
		}
		
		if ($client->getSuggestions() == null) {
			$result['body']['suggest']['text'] = elgg_extract('query', $params);
			$result['body']['suggest']['suggestions']['phrase'] = [
				"field" => "_all",
				"max_errors" => 2,
				"size" => 1,
				"real_word_error_likelihood" => 0.95,
				"gram_size" => 1,
				"direct_generator" => [[
					"field" => "_all",
					"suggest_mode" => "popular",
					"min_word_length" => 1
				]]
			];
		}
				
		// query
 		$result['body']['query']['indices']['index'] = $client->getIndex();
 		$result['body']['query']['indices']['query']['bool']['must']['match']['_all'] = $params['query'];
 		$result['body']['query']['indices']['no_match_query']['bool']['must']['term']['_all'] = $params['query'];
		
		$result = self::getAccessParamsForSearch($result);

		return $result;
	}
	
	protected static function prepareSearchParams($params) {
		// maybe trigger a hook to allow adjustments/additions to the search params
		
		return $params;
	}
	
	public function getAccessParamsForSearch($result, $user_guid = 0) {
		$client = elasticsearch_get_client();
		
		if (!$client) {
			return $result;
		}
		
		$user_guid = sanitise_int($user_guid, false);
		
		if (empty($user_guid)) {
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if (elgg_get_ignore_access()) {
			return $result;
		}

		$access_filter = [];
		if (!empty($user_guid)) {
			// check for owned content
			$access_filter[]['term']['owner_guid'] = $user_guid;
			
			// add friends check
			$friends = elgg_get_entities_from_relationship(array(
				'type' => 'user',
				'relationship' => 'friend',
				'relationship_guid' => $user_guid,
				'inverse_relationship' => true,
				'limit' => false,
				'callback' => function ($row) {
					return $row->guid;
				}
			));
			
			if (!empty($friends)) {
				$access_filter[] = [
					'bool' => [
						'must' => [
				
							'term' => [
								'owner_guid' => $friends
							],
							'term' => [
								'access_id' => ACCESS_FRIENDS
							]
						]
					]
				];
			}
		}
		
		// add acl filter
		$access_array = get_access_array($user_guid);
		if (!empty($access_array)) {
			$access_filter[]['terms']['access_id'] = $access_array;
		}
		
		if (empty($access_filter)) {
			return $result;
		}
		
		$result['body']['filter']['indices']['index'] = $client->getIndex();
		$result['body']['filter']['indices']['filter']['bool']['should'] = $access_filter;
		
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
				'href' => elgg_http_add_url_query_elements($url, ['sort' => $item]),
				'parent_name' => 'sort',
				'selected' => ($current_sort === $item),
				'title' => $title
			]);
		}
				
		return $returnvalue;
	}
}
