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
		$result['body']['query']['indices']['index'] = $index;
		if (!empty($this->params['query'])) {
			$result['body']['query']['indices']['query'] = $this->params['query'];
			
			$query_text = $this->params['query_text'];
			if (!empty($query_text)) {
				// generate
				$fields = [
					'title',
					'description',
					'tags'
				];
					
				$no_match_query['bool']['should'] = [];
				foreach ($fields as $field) {
					$no_match_query['bool']['should'][] = [
						'match' => [
							$field => [
								'query' => $query_text
							]
						]
					];
				}
				
				$result['body']['query']['indices']['no_match_query'] = $no_match_query;
			} else {
				$result['body']['query']['indices']['no_match_query']['bool']['must']['match_all'] = [];
			}
		} else {
			$result['body']['query']['indices']['query']['bool']['must']['match_all'] = [];
			$result['body']['query']['indices']['no_match_query']['bool']['must']['match_all'] = [];
		}
				
		// filter
		if (!empty($this->params['filter'])) {
			$result['body']['filter']['indices']['index'] = $index;
			$result['body']['filter']['indices']['filter'] = $this->params['filter'];
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
	
	public function setQueryText($query) {
		$this->params['query_text'] = $query;
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
		
		$filter['bool']['should'] = $access_filter;
		$this->addFilter($filter);
	}
}
