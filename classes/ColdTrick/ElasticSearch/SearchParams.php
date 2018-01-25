<?php

namespace ColdTrick\ElasticSearch;

class SearchParams {
	
	/**
	 * The search client
	 *
	 * @var \ColdTrick\ElasticSearch\Client
	 */
	protected $client;
	
	/**
	 * The search params
	 *
	 * @var array
	 */
	protected $params;
	
	/**
	 * SearchParams constructor
	 *
	 * @param array $params Array of injectable parameters
	 */
	public function __construct($params = []) {
		$this->client = $params['client'];
		$this->params = [];
	}
	
	public function execute($body = null) {
		if ($body == null) {
			$body = $this->getBody();
		}
		
		$result = $this->client->search($body);
		
		$result = new SearchResult($result, $this->params);
		
		$suggest = $result->getSuggestions();
		if (!empty($suggest)) {
			$this->client->setSuggestions($suggest);
		}
		
		// reset search params after each search
		$this->params = [];
		
		return $result;
	}

	public function count($body = null) {
		if ($body == null) {
			$body = $this->getBody($count = true);
		}
		$body['search_type'] = 'count';
		
		return $this->execute($body);
	}
	
	protected function getBody($count = false) {
		$result = [];
		
		// index
		$index = $this->client->getIndex();
		if (!empty($this->getParam('index'))) {
			$index = $this->getParam('index');
			$result['index'] = $index;
		}
		
		// type
		if (!empty($this->getParam('type'))) {
			$result['type'] = $this->getParam('type');
		}
		
		// query
		if (!empty($this->getParam('query'))) {
			$result['body']['query']['function_score']['query'] = $this->getParam('query');
		} else {
			$result['body']['query']['function_score']['query']['bool']['must']['match_all'] = [];
		}
				
		// filter
		$filter = $this->getParam('filter');
		$no_match_filter = $this->getParam('no_match_filter');
		if (!empty($filter) || !empty($no_match_filter)) {
			if (empty($filter)) {
				$filter = 'all';
			}
			if (empty($no_match_filter)) {
				$no_match_filter = 'all';
			}
			
			$result['body']['filter']['indices']['index'] = $index;
			$result['body']['filter']['indices']['filter'] = $filter;
			$result['body']['filter']['indices']['no_match_filter'] = $no_match_filter;
		}
		
		// track scores
		if ($this->getParam('track_scores') !== null) {
			$result['body']['track_scores'] = $this->getParam('track_scores');
		}
		
		if (!$count) {
			// pagination
			if (!empty($this->getParam('from'))) {
				$result['from'] = $this->getParam('from');
			}
			if (!empty($this->getParam('size'))) {
				$result['size'] = $this->getParam('size');
			}
			
			// apply type boosting
			$functions = self::getScoreFunctions();
			if (!empty($functions)) {
				$result['body']['query']['function_score']['functions'] = $functions;
			}
			
			// sort
			if (!empty($this->getParam('sort'))) {
				$result['body']['sort'] = $this->getParam('sort');
			}
			
			// suggestion
			if (!empty($this->getParam('suggest')) && ($this->client->getSuggestions() == null)) {
				// only fetch suggestion once
				$result['body']['suggest'] = $this->getParam('suggest');
			}
			
			// highlighting
			if (!empty($this->getParam('highlight'))) {
				$result['body']['highlight'] = $this->getParam('highlight');
			}
		}
		
		return $result;
	}
	
	public function setIndex($index) {
		$this->params['index'] = $index;
	}
	
	/**
	 * Sets the type to be search
	 *
	 * @param string|array $type the type to be searched
	 *
	 * @return void
	 */
	public function setType($type) {
		$this->params['type'] = $type;
	}

	public function addType($type) {
		$types = (array) $this->getType();
		$types[] = $type;
	
		$this->params['type'] = $types;
	}
	
	public function getType() {
		return $this->getParam('type');
	}
	
	public function addFilter($filter) {
		$this->params['filter'] = array_merge_recursive($this->getParam('filter', []), $filter);
	}
	
	public function setFilter($filter) {
		$this->params['filter'] = $filter;
	}
	
	public function getFilter() {
		return $this->getParam('filter');
	}

	public function addNoMatchFilter($filter) {
		$this->params['no_match_filter'] = array_merge_recursive($this->getParam('no_match_filter', []), $filter);
	}
	
	public function setNoMatchFilter($filter) {
		$this->params['no_match_filter'] = $filter;
	}
	
	public function getNoMatchFilter() {
		return $this->getParam('no_match_filter');
	}
		
	public function addQuery($query = []) {
		$this->params['query'] = array_merge_recursive($this->getParam('query', []), $query);
	}

	public function setQuery($query = []) {
		$this->params['query'] = $query;
	}

	public function getQuery() {
		return $this->getParam('query');
	}
	
	public function trackScores($track_scores = true) {
		$this->params['track_scores'] = $track_scores;
	}
	
	public function setSort($sort = []) {
		$this->params['sort'] = $sort;
	}
	
	/**
	 * Appends/replaces an extra sort field config
	 *
	 * @param string $field       name of the field to sort on
	 * @param array  $sort_config configuration of the sort (like order)
	 */
	public function addSort($field, $sort_config = []) {
		if (empty($field)) {
			return;
		}
		
		if (empty($sort_config)) {
			if ($field == '_score') {
				$sort_config = ['order' => 'desc'];
			} else {
				$sort_config = ['order' => 'asc'];
			}
		}
		
		if ($field == '_score') {
			$this->trackScores(true);
		}
		
		$this->params['sort'][$field] = $sort_config;
	}
	
	public function getSort() {
		return $this->getParam('sort');
	}
	
	public function setSize($size) {
		$this->params['size'] = (int) $size;
		
	}
	
	public function setLimit($limit) {
		$this->setSize($limit);
	}
	
	public function setFrom($from) {
		$this->params['from'] = (int) $from;
	}
	
	public function setOffset($offset) {
		$this->setFrom($offset);
	}
	
	public function setSuggestion($query) {
		if (empty($query)) {
			unset($this->params['suggest']);
		}
		
		$this->params['suggest']['text'] = $query;
		$this->params['suggest']['suggestions']['phrase'] = [
			"field" => "_all",
			"direct_generator" => [[
				"field" => "_all",
				"suggest_mode" => "missing",
			]],
		];
	}

	public function setHighlight($data) {
	
		if (empty($data)) {
			unset($this->params['highlight']);
			return;
		}
		
		$this->params['highlight'] = $data;
	}
	
	public function getHighlight() {
		return $this->getParam('highlight', []);
	}
	
	public function addEntityAccessFilter($user_guid = 0) {
		$user_guid = sanitise_int($user_guid, false);
		
		if (empty($user_guid)) {
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if (elgg_check_access_overrides($user_guid)) {
			return;
		}
		
		$access_filter = [];
		if (!empty($user_guid)) {
			// check for owned content
			$access_filter[]['term']['owner_guid'] = $user_guid;
				
			// add friends check
			$friends = elgg_get_entities_from_relationship([
				'type' => 'user',
				'relationship' => 'friend',
				'relationship_guid' => $user_guid,
				'inverse_relationship' => true,
				'limit' => false,
				'callback' => function ($row) {
					return $row->guid;
				}
			]);
				
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
			return;
		}
		
		$filter = [];
		$filter['bool']['must'][]['bool']['should'] = $access_filter;
		$this->addFilter($filter);
	}
	
	public function getParams() {
		return $this->params;
	}
	
	/**
	 * Get a value of a param
	 *
	 * @param string $name    the parameter to get
	 * @param mixed  $default the default return value (default: null)
	 *
	 * @return mixed
	 */
	protected function getParam($name, $default = null) {
		if (!isset($this->params[$name])) {
			return $default;
		}
		
		return $this->params[$name];
	}

	/**
	 * Returns an array of functions to be used in the function_score array
	 *
	 * @return array
	 */
	protected static function getScoreFunctions() {
		
		$type_subtypes = elasticsearch_get_registered_entity_types_for_search();
		$types = SearchHooks::entityTypeSubtypesToSearchTypes($type_subtypes);
		if (empty($types)) {
			return [];
		}
		
		$result = [];
		foreach ($types as $type) {
			$weight = (float) elgg_get_plugin_setting("type_boosting_$type", 'elasticsearch');
			if (!($weight > 0) || $weight == 1) {
				continue;
			}
			
			$result[] = [
				'filter' => [
					'term' => [
						'_type' => $type,
					],
				],
				'weight' => $weight,
			];
		}
		
		return $result;
	}
}
