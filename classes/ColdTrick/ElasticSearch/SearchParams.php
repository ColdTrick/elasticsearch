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
	}
	
	public function execute($body = null) {
		if ($body == null) {
			$body = $this->getBody();
		}
				
		$result = $this->client->search($body);
				
		$result = new SearchResult($result, $this->params);
		
		// reset search params after each search
		$this->params = null;
			
		return $result;
	}
	
	protected function getBody() {
		$result = [];
		
		// index
		if (!empty($this->params['index'])) {
			$result['index'] = $this->params['index'];
		}
		
		// type
		if (!empty($this->params['type'])) {
			$result['type'] = $this->params['type'];
		}
		
		// query
		$result['body']['query']['indices']['index'] = $this->client->getIndex();
		if (!empty($this->params['query'])) {
			$result['body']['query']['indices']['query'] = $this->params['query'];
			$result['body']['query']['indices']['no_match_query'] = $this->params['query'];
		} else {
			$result['body']['query']['indices']['query']['bool']['must']['match_all'] = [];
			$result['body']['query']['indices']['no_match_query']['bool']['must']['match_all'] = [];
		}
		
		// pagination
		if (!empty($this->params['from'])) {
			$result['from'] = $this->params['from'];
		}
		if (!empty($this->params['size'])) {
			$result['size'] = $this->params['size'];
		}
		
		// filter
		if (!empty($this->params['filter'])) {
			$result['body']['filter']['indices']['index'] = $this->client->getIndex();
			$result['body']['filter']['indices']['filter'] = $this->params['filter'];
		}
		
		// sort
		if (!empty($this->params['sort'])) {
			$result['sort'] = $this->params['sort'];
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
	
	public function addFilter($type, $filter) {
		$this->params['filter'][$type][] = $filter;
	}
	
	public function setFilter($filter) {
		$this->params['filter'] = $filter;
	}
	
	public function setQuery($query = []) {
		$this->params['query'] = $query;
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
	public function addSort($field, $sort_config) {
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
	
	public function addEntityAccessFilter($user_guid = 0) {
		$user_guid = sanitise_int($user_guid, false);
		
		if (empty($user_guid)) {
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if (elgg_get_ignore_access()) {
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
