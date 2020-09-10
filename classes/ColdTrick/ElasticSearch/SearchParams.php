<?php

namespace ColdTrick\ElasticSearch;

use ColdTrick\ElasticSearch\SearchParams\Initialize;

class SearchParams {

	use Initialize;
	
	/**
	 * The search service
	 *
	 * @var \ColdTrick\ElasticSearch\Di\SearchService
	 */
	protected $service;
	
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
	public function __construct(array $params = []) {
		$this->service = $params['service'];
		$this->params = [];
	}
	
	/**
	 * Build body for elasticsearch client
	 *
	 * @param bool $count result should be a count (default: false)
	 *
	 * @return array
	 */
	public function getBody(bool $count = false) {
		$result = [];
		
		// index
		$index = $this->service->getIndex();
		if (!empty($this->getParam('index'))) {
			$index = $this->getParam('index');
			$result['index'] = $index;
		}
		
		// query
		if (!empty($this->getParam('query'))) {
			$result['body']['query']['function_score']['query']['bool']['must'] = $this->getParam('query');
		} else {
			$result['body']['query']['function_score']['query']['bool']['must']['match_all'] = (object) [];
		}
				
		// filter
		$filter = $this->getParam('filter');
		if (!empty($filter)) {
			if ($count) {
				$query = $result['body']['query'];
				unset($result['body']['query']);
				
				$result['body']['query']['bool'] = [
					'must' => $query,
					'filter' => $filter,
				];
			} else {
				$result['body']['query']['function_score']['query']['bool']['filter'] = $filter;
			}
		}
		
		if (!$count) {
			// track scores
			if ($this->getParam('track_scores') !== null) {
				$result['body']['track_scores'] = $this->getParam('track_scores');
			}
			
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
			
			// highlighting
			if (!empty($this->getParam('highlight'))) {
				$result['body']['highlight'] = $this->getParam('highlight');
			}
			
			// aggregation
			$aggregation = $this->getAggregation();
			if (!empty($aggregation)) {
				$result['body']['aggs']['wrapper'] = [
					'aggs' => $aggregation,
					'filter' => $filter ?: new \stdClass(),
				];
			}
		}
		
		return $result;
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
		
		$result = [];
		
		// add function scoring for type boosting
		$types = elasticsearch_get_types_for_boosting();
		if (!empty($types)) {
			foreach ($types as $type) {
				$weight = (float) elgg_get_plugin_setting("type_boosting_$type", 'elasticsearch');
				if (!($weight > 0) || $weight == 1) {
					continue;
				}
				
				$result[] = [
					'filter' => [
						'term' => [
							'indexed_type' => $type,
						],
					],
					'weight' => $weight,
				];
			}
		}
		
		$decay_offset = (int) elgg_get_plugin_setting('decay_offset', 'elasticsearch', 0);
		$decay_scale = (int) elgg_get_plugin_setting('decay_scale', 'elasticsearch');
		$decay_decay = elgg_get_plugin_setting('decay_decay', 'elasticsearch');
		$decay_time_field = elgg_get_plugin_setting('decay_time_field', 'elasticsearch');
		
		if (!empty($decay_scale) && !empty($decay_decay) && !empty($decay_time_field)) {
			$result[] = [
				'gauss' => [
					$decay_time_field => [
						'origin' => date('c'),
						'scale' => "{$decay_scale}d",
						'offset' => "{$decay_offset}d",
						'decay' => $decay_decay,
					],
				],
			];
		}

		return $result;
	}
	
	/**
	 * Set the index to search in
	 *
	 * @param string $index the name of the index
	 *
	 * @return void
	 */
	public function setIndex(string $index) {
		$this->params['index'] = $index;
	}
	
	/**
	 * Add filter to search params
	 *
	 * @param array $filter new filter
	 *
	 * @return void
	 */
	public function addFilter(array $filter) {
		$this->params['filter'] = array_merge_recursive($this->getParam('filter', []), $filter);
	}
	
	/**
	 * Set filter for search params
	 *
	 * @param array $filter new filter
	 *
	 * @return void
	 */
	public function setFilter(array $filter) {
		$this->params['filter'] = $filter;
	}
	
	/**
	 * Get filter for search
	 *
	 * @return mixed
	 */
	public function getFilter() {
		return $this->getParam('filter');
	}

	/**
	 * Add no match filter to search params
	 *
	 * @param array $filter new filter
	 *
	 * @return void
	 */
	public function addNoMatchFilter(array $filter) {
		$this->params['no_match_filter'] = array_merge_recursive($this->getParam('no_match_filter', []), $filter);
	}
	
	/**
	 * Set no match filter for search params
	 *
	 * @param array $filter new filter
	 *
	 * @return void
	 */
	public function setNoMatchFilter(array $filter) {
		$this->params['no_match_filter'] = $filter;
	}
	
	/**
	 * Get no match filter for search
	 *
	 * @return mixed
	 */
	public function getNoMatchFilter() {
		return $this->getParam('no_match_filter');
	}
	
	/**
	 * Add query to search params
	 *
	 * @param array $query new query
	 *
	 * @return void
	 */
	public function addQuery(array $query = []) {
		$this->params['query'] = array_merge_recursive($this->getParam('query', []), $query);
	}

	/**
	 * Set query for search params
	 *
	 * @param array $query new query
	 *
	 * @return void
	 */
	public function setQuery(array $query = []) {
		$this->params['query'] = $query;
	}

	/**
	 * Get query for search
	 *
	 * @return mixed
	 */
	public function getQuery() {
		return $this->getParam('query');
	}
	
	/**
	 * Track search scores
	 *
	 * @param bool $track_scores should scored be tracked
	 *
	 * @return void
	 */
	public function trackScores(bool $track_scores = true) {
		$this->params['track_scores'] = $track_scores;
	}
	
	/**
	 * Set sorting params for search
	 *
	 * @param array $sort sorting
	 *
	 * @return void
	 */
	public function setSort(array $sort = []) {
		$this->params['sort'] = $sort;
	}
	
	/**
	 * Appends/replaces an extra sort field config
	 *
	 * @param string $field       name of the field to sort on
	 * @param array  $sort_config configuration of the sort (like order)
	 *
	 * @return void
	 */
	public function addSort(string $field, $sort_config = []) {
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
	
	/**
	 * Get sorting params for search
	 *
	 * @return mixed
	 */
	public function getSort() {
		return $this->getParam('sort');
	}
	
	/**
	 * Set limit on search results
	 *
	 * @param int $size limit
	 *
	 * @return void
	 */
	public function setSize(int $size) {
		$this->params['size'] = $size;
	}
	
	/**
	 * Set limit on search results
	 *
	 * @param int $limit limit
	 *
	 * @return void
	 * @see self::setSize()
	 */
	public function setLimit(int $limit) {
		$this->setSize($limit);
	}
	
	/**
	 * Set offset for search
	 *
	 * @param int $from offset
	 *
	 * @return void
	 */
	public function setFrom(int $from) {
		$this->params['from'] = $from;
	}
	
	/**
	 * Set offset for search
	 *
	 * @param int $offset offset
	 *
	 * @return void
	 * @see self::setFrom()
	 */
	public function setOffset(int $offset) {
		$this->setFrom($offset);
	}
	
	/**
	 * Set highlight settings
	 *
	 * @param array $data highlight settings
	 *
	 * @return void
	 */
	public function setHighlight(array $data = []) {
	
		if (empty($data)) {
			unset($this->params['highlight']);
			return;
		}
		
		$this->params['highlight'] = $data;
	}
	
	/**
	 * Gte highlight settings
	 *
	 * @return mixed
	 */
	public function getHighlight() {
		return $this->getParam('highlight', []);
	}
	
	/**
	 * Add access filters
	 *
	 * @param int $user_guid user guid to set filter for (default: current users)
	 *
	 * @return void
	 */
	public function addEntityAccessFilter(int $user_guid = 0) {
		if ($user_guid < 1) {
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if (_elgg_services()->userCapabilities->canBypassPermissionsCheck($user_guid)) {
			return;
		}
		
		$access_filter = [];
		if (!empty($user_guid)) {
			// check for owned content
			$access_filter[]['term']['owner_guid'] = $user_guid;
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
	
	/**
	 * Get search params
	 *
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}
	
	/**
	 * Reset the search params after a search
	 *
	 * @return void
	 */
	public function resetParams() {
		$this->params = [];
	}
	
	/**
	 * Set aggregation search params
	 *
	 * @param array $aggregation aggregation
	 *
	 * @return void
	 */
	public function setAggregation(array $aggregation = []) {
		if (empty($aggregation)) {
			unset($this->params['aggregation']);
			return;
		}
		
		$this->params['aggregation'] = $aggregation;
	}
	
	/**
	 * Add aggregation to search params
	 *
	 * @param array $aggregation aggregation
	 *
	 * @return void
	 */
	public function addAggregation(array $aggregation) {
		$this->params['aggregation'] = array_merge_recursive($this->getParam('aggregation', []), $aggregation);
	}
	
	/**
	 * Get aggregation search params
	 *
	 * @return mixed
	 */
	public function getAggregation() {
		return $this->getParam('aggregation');
	}
}
