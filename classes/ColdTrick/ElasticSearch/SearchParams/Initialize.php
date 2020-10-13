<?php

namespace ColdTrick\ElasticSearch\SearchParams;

use Elgg\Database\LegacyQueryOptionsAdapter;
use Elgg\Values;

trait Initialize {
	
	use LegacyQueryOptionsAdapter;
	
	/**
	 * Convert search params from elgg_search to internal workings of Elasticsearch
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	public function initializeSearchParams(array $search_params = []) {
		
		// normalize everything
		$search_params = $this->normalizeOptions($search_params);
		
		// apply limit and offset
		$this->setFrom(elgg_extract('offset', $search_params, 0));
		$this->setLimit(elgg_extract('limit', $search_params, elgg_get_config('default_limit')));
		
		$this->initializeQuery($search_params);
		$this->initializeContainerGUID($search_params);
		$this->initializeOwnerGUID($search_params);
		$this->initializeSorting($search_params);
		$this->initializeTypeSubtypePairs($search_params);
		$this->initializeTimeConstraints($search_params);
		$this->initializeAccessConstraints($search_params);
	}
	
	/**
	 * Set the search query
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeQuery(array $search_params = []) {
		
		$query = elgg_extract('query', $search_params);
		if (empty($query )) {
			return;
		}

		if (elgg_extract('tokenize', $search_params) === false && stristr($query, ' ')) {
			$query = '"' . $query . '"';
		} elseif (stristr($query, ' ')) {
			$query = $query . ' || "' . $query . '"';
		}
		
		$query_fields = $this->getQueryFields($search_params);
		
		$elastic_query = [];
		
		$elastic_query['bool']['must'][]['simple_query_string'] = [
			'fields' => $query_fields,
			'query' => $query,
			'default_operator' => 'AND',
		];
											
		if (!elgg_extract('count', $search_params, false)) {
			$original_query = elgg_extract('query', $search_params);
			
			$this->setHighlight($this->getDefaultHighlightParams($original_query));
		}
		
		$this->setQuery($elastic_query);
	}
	
	/**
	 * Get the query fields from the params and apply field boosting
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return string[]
	 */
	protected function getQueryFields(array $search_params = []) {
		
		$result = [];
		
		$search_fields = elgg_extract('fields', $search_params, []);
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
						// support user profile fields
						if (strpos($name, 'profile:') === 0) {
							$name = substr($name, strlen('profile:'));
							$result[] = "metadata.{$name}";
							break;
						}
						break;
					case 'private_settings':
						// no yet supported
						break;
				}
			}
		}
		
		// apply field boosting
		$field_boosting = (array) elgg_extract('field_boosting', $search_params, []);
		
		foreach ($result as $index => $fieldname) {
			$boost = elgg_extract($fieldname, $field_boosting);
			if (elgg_is_empty($boost)) {
				continue;
			}
			
			$result[$index] = "{$fieldname}^{$boost}";
		}
		
		return $result;
	}
	
	/**
	 * Get the highlighting query
	 *
	 * @param string $query the search query
	 *
	 * @return array
	 */
	protected function getDefaultHighlightParams(string $query) {
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
	
	/**
	 * Apply container_guid filter
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeContainerGUID(array $search_params = []) {
		
		$container_guid = (array) elgg_extract('container_guids', $search_params, []);
		$container_guid = array_filter(array_map(function ($v) {
			return (int) $v;
		}, $container_guid));
		if (empty($container_guid)) {
			return;
		}
		
		$container_filter = [];
		$container_filter['bool']['must'][]['term']['container_guid'] = $container_guid;
		$this->addFilter($container_filter);
	}
	
	/**
	 * Apply owner_guid filter
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeOwnerGUID(array $search_params = []) {
		
		$owner_guid = (array) elgg_extract('owner_guids', $search_params);
		$owner_guid = array_filter(array_map(function ($v) {
			return (int) $v;
		}, $owner_guid));
		if (empty($owner_guid)) {
			return;
		}
		
		$owner_filter = [];
		$owner_filter['bool']['must'][]['term']['owner_guid'] = $owner_guid;
		$this->addFilter($owner_filter);
	}
	
	/**
	 * Apply sorting
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeSorting(array $search_params = []) {
		
		$sort = elgg_extract('sort', $search_params, 'relevance');
		$order = elgg_extract('order', $search_params, 'desc');
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
			
				$this->addSort('_score', []);
				$sort_field = 'time_created';
				$sort = 'desc';
				break;
			// support default elgg sort fields
			case 'time_created':
			case 'time_updated':
			case 'last_action':
				$sort_field = $sort;
				break;
			case 'name':
				$sort_field = 'title.raw';
				break;
		}
		
		if (!empty($sort_field)) {
			$this->addSort($sort_field, [
				'order' => $order,
				'unmapped_type' => 'long',
				'missing' => '_last',
			]);
		}
	}
	
	/**
	 * Add type/subtype limitations
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeTypeSubtypePairs(array $search_params = []) {
		
		$type_subtype_pairs = elgg_extract('type_subtype_pairs', $search_params);
		if (empty($type_subtype_pairs)) {
			return;
		}
		
		$types = [];
		
		foreach ($type_subtype_pairs as $type => $subtypes) {
			if (empty($subtypes)) {
				$types[] = "{$type}.{$type}";
				continue;
			}
			
			foreach ($subtypes as $subtype) {
				$types[] = "{$type}.{$subtype}";
			}
		}
		
		$type_filter['terms']['indexed_type'] = $types;
		$filter['bool']['must'][] = $type_filter;
		
		$this->addFilter($filter);
	}
	
	/**
	 * Add time constraints
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeTimeConstraints(array $search_params = []) {
		
		$make_filter = function($time, $time_field, $direction) {
			try {
				$date = Values::normalizeTime($time);
			} catch (\DataFormatException $e) {
				return false;
			}
			
			$range['range'][$time_field][$direction] = $date->format('c');
			$range_filter['bool']['must'][] = $range;
			
			return $range_filter;
		};
		
		// created_before
		$created_before = elgg_extract('created_before', $search_params);
		if (!empty($created_before)) {
			$filter = $make_filter($created_before, 'time_created', 'lte');
			if (!empty($filter)) {
				$this->addFilter($filter);
			}
		}
		
		// created_after
		$created_after = elgg_extract('created_after', $search_params);
		if (!empty($created_after)) {
			$filter = $make_filter($created_after, 'time_created', 'gte');
			if (!empty($filter)) {
				$this->addFilter($filter);
			}
		}
		
		// updated_before
		$updated_before = elgg_extract('updated_before', $search_params);
		if (!empty($updated_before)) {
			$filter = $make_filter($updated_before, 'time_updated', 'lte');
			if (!empty($filter)) {
				$this->addFilter($filter);
			}
		}
		
		// updated_after
		$updated_after = elgg_extract('updated_after', $search_params);
		if (!empty($updated_after)) {
			$filter = $make_filter($updated_after, 'time_updated', 'gte');
			if (!empty($filter)) {
				$this->addFilter($filter);
			}
		}
	
		// last_action_before
		$last_action_before = elgg_extract('last_action_before', $search_params);
		if (!empty($last_action_before)) {
			$filter = $make_filter($last_action_before, 'last_action', 'lte');
			if (!empty($filter)) {
				$this->addFilter($filter);
			}
		}
		
		// last_action_after
		$last_action_after = elgg_extract('last_action_after', $search_params);
		if (!empty($last_action_after)) {
			$filter = $make_filter($last_action_after, 'last_action', 'gte');
			if (!empty($filter)) {
				$this->addFilter($filter);
			}
		}
	}
	
	/**
	 * Add access_ids constraints
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeAccessConstraints(array $search_params = []) {
		
		$access_ids = elgg_extract('access_ids', $search_params);
		if (empty($access_ids)) {
			return;
		}
		
		$access_filter = [];
		$access_filter[]['terms']['access_id'] = $access_ids;
		
		$filter = [];
		$filter['bool']['must'][] = $access_filter;
		
		$this->addFilter($filter);
	}
}
