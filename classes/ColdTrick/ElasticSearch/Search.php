<?php

namespace ColdTrick\ElasticSearch;

class Search {
	
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
		$search_params = self::getDefaultSearchParamsForHook($params);
		
		return self::performSearch($search_params);
	}
	
	/**
	 * Hook to change the custom types that need to be searched
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function getCustomTypes($hook, $type, $returnvalue, $params) {
		if (!is_array($returnvalue)) {
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
			$source = elgg_extract('_source', $hit);
			$body = elgg_extract('body', $source);
			$body = elgg_extract('body', $body);

			$entity = self::resultBodyToEntity($body);
			
			if (!$entity) {
				continue;
			}
			
			// set correct search highlighting
			$title = elgg_extract('title', $body, elgg_extract('name', $body));
			$title = search_get_highlighted_relevant_substrings($title, $params['q']);
			$entity->setVolatileData('search_matched_title', $title);
				
			$desc = elgg_extract('description', $body);
			$desc = search_get_highlighted_relevant_substrings($desc, $params['q']);
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
				case 'read_access':
					$row->access_id = $value;
					break;
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
		
		return entity_row_to_elggstar($row);
	}
	
	protected static function getDefaultSearchParamsForHook($params) {
		$result = [
			'from' => $params['offset'],
			'size' => $params['limit'],
			'q' => $params['query']
		];
		
		return $result;
	}
	
	protected static function prepareSearchParams($params) {
		// maybe trigger a hook to allow adjustments/additions to the search params
		
		return $params;
	}
}
