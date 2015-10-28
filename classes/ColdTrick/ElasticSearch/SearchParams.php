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
		$this->params = null;
		
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
		if (!empty($this->params['index'])) {
			$index = $this->params['index'];
			$result['index'] = $index;
		}
		
		// type
		if (!empty($this->params['type'])) {
			$result['type'] = $this->params['type'];
		}
		
		// query
		if (!empty($this->params['query'])) {
			$result['body']['query'] = $this->params['query'];
		} else {
			$result['body']['query']['bool']['must']['match_all'] = [];
		}
				
		// filter
		$filter = elgg_extract('filter', $this->params);
		$no_match_filter = elgg_extract('no_match_filter', $this->params);
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
		if (isset($this->params['track_scores'])) {
			$result['body']['track_scores'] = $this->params['track_scores'];
		}
		
		if (!$count) {
			// pagination
			if (!empty($this->params['from'])) {
				$result['from'] = $this->params['from'];
			}
			if (!empty($this->params['size'])) {
				$result['size'] = $this->params['size'];
			}
			
			// sort
			if (!empty($this->params['sort'])) {
				$result['body']['sort'] = $this->params['sort'];
			}
			
			// suggestion
			if (!empty($this->params['suggest']) && ($this->client->getSuggestions() == null)) {
				// only fetch suggestion once
				$result['body']['suggest'] = $this->params['suggest'];
			}
			
			// highlighting
			// global settings
			$result['body']['highlight']['encoder'] = 'html';
			$result['body']['highlight']['pre_tags'] = [
				'<strong class="search-highlight search-highlight-color1">',
			];
			$result['body']['highlight']['post_tags'] = [
				'</strong>',
			];
			$result['body']['highlight']['number_of_fragments'] = 3;
			// title
			$result['body']['highlight']['fields']['title'] = [
				'number_of_fragments' => 0,
			];
			// description
			$des = new \stdClass();
			$result['body']['highlight']['fields']['description'] = $des;
			// tags
			$result['body']['highlight']['fields']['tags'] = [
				'number_of_fragments' => 0,
			];
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
		return $this->params['type'];
	}
	
	public function addFilter($filter) {
		if (!isset($this->params['filter'])) {
			$this->params['filter'] = [];
		}
		$this->params['filter'] = array_merge_recursive($this->params['filter'], $filter);
	}
	
	public function setFilter($filter) {
		$this->params['filter'] = $filter;
	}
	
	public function getFilter() {
		return $this->params['filter'];
	}

	public function addNoMatchFilter($filter) {
		if (!isset($this->params['no_match_filter'])) {
			$this->params['no_match_filter'] = [];
		}
		$this->params['no_match_filter'] = array_merge_recursive($this->params['no_match_filter'], $filter);
	}
	
	public function setNoMatchFilter($filter) {
		$this->params['no_match_filter'] = $filter;
	}
	
	public function getNoMatchFilter() {
		return $this->params['no_match_filter'];
	}
		
	public function addQuery($query = []) {
		if (!isset($this->params['query'])) {
			$this->params['query'] = [];
		}
		$this->params['query'] = array_merge_recursive($this->params['query'], $query);
	}

	public function setQuery($query = []) {
		$this->params['query'] = $query;
	}

	public function getQuery() {
		return $this->params['query'];
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
		return elgg_extract('sort', $this->params);
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
		
		$filter['bool']['must']['bool']['should'] = $access_filter;
		$this->addFilter($filter);
	}
	
	public function getParams() {
		return $this->params;
	}
}
