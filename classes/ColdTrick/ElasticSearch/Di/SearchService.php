<?php

namespace ColdTrick\ElasticSearch\Di;

use ColdTrick\ElasticSearch\SearchParams;
use ColdTrick\ElasticSearch\SearchResult;
use Elasticsearch\Common\Exceptions\ElasticsearchException;

class SearchService extends BaseClientService {

	/**
	 * Search aggregations
	 *
	 * @var array
	 */
	private $aggregations;
	
	/**
	 * @var SearchParams
	 */
	private $search_params;
	
	/**
	 * {@inheritDoc}
	 */
	public static function name() {
		return 'elasticsearch.searchservice';
	}
	
	/**
	 * Inspect a GUID in Elasticsearch
	 *
	 * @param int  $guid       the GUID to inspect
	 * @param bool $return_raw return full return or only _source (default: false)
	 *
	 * @return false|array
	 */
	public function inspect(int $guid, bool $return_raw = false) {
		
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$result = $this->getClient()->get([
				'id' => $guid,
				'index' => $this->getIndex(),
			]);
			
			if ($return_raw) {
				return $result;
			}
			
			return elgg_extract('_source', $result);
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Perform a search on the Elasticsearch client
	 *
	 * @param array $params search params
	 *
	 * @return false|array
	 */
	public function rawSearch(array $params = []) {
		
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			return $this->getClient()->search($params);
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return [];
	}
	
	/**
	 * Provide the Elgg search parameters before executing a search operation
	 *
	 * @param array $search_params the Elgg search parameters
	 *
	 * @return void
	 */
	public function initializeSearchParams(array $search_params = []) {
		$this->getSearchParams()->initializeSearchParams($search_params);
		$this->getSearchParams()->addEntityAccessFilter();
	}
	
	/**
	 * Execute a search query
	 *
	 * @param array $body optional search body
	 *
	 * @return false|\ColdTrick\ElasticSearch\SearchResult
	 */
	public function search(array $body = []) {
		
		if (!$this->isClientReady()) {
			return false;
		}
		
		if (empty($body)) {
			$body = $this->getSearchParams()->getBody();
		}
		
		if (!isset($body['index'])) {
			$body['index'] = $this->getSearchIndex();
		}
		
		$this->requestToScreen($body, 'SEARCH');
		
		$result = [];
		try {
			$result = $this->getClient()->search($body);
		} catch (ElasticsearchException $e) {
			// exception already logged by Elasticsearch
		}
		
		$result = new SearchResult($result, $this->getSearchParams()->getParams());
		
		$aggregations = $result->getAggregations();
		if (!empty($aggregations)) {
			$this->setAggregations(elgg_extract('wrapper', $aggregations));
		}
		
		// reset search params after each search
		$this->getSearchParams()->resetParams();
		
		return $result;
	}
	
	/**
	 * Execute a count query
	 *
	 * @param array $body optional search body
	 *
	 * @return false|\ColdTrick\ElasticSearch\SearchResult
	 */
	public function count(array $body = []) {
		
		if (!$this->isClientReady()) {
			return false;
		}
		
		if (empty($body)) {
			$body = $this->getSearchParams()->getBody(true);
		}
		
		if (!isset($body['index'])) {
			$body['index'] = $this->getSearchIndex();
		}
		
		$this->requestToScreen($body, 'COUNT');
		
		$result = [];
		try {
			$result = $this->getClient()->count($body);
		} catch (ElasticsearchException $e) {
			// exception already logged by Elasticsearch
		}
		
		// reset search params after each search
		$this->getSearchParams()->resetParams();
		
		return new SearchResult($result, $this->getSearchParams()->getParams());
	}
	
	/**
	 * Scroll through a search setup
	 *
	 * @param array $params search params
	 *
	 * @return false|array
	 */
	public function scroll(array $params) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		return $this->getClient()->scroll($params);
	}
	
	/**
	 * Clear a search scroll
	 *
	 * @param array $params search params
	 *
	 * @return false|array
	 */
	public function clearScroll(array $params) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		return $this->getClient()->clearScroll($params);
	}
	
	/**
	 * Set aggregations from search  result
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function setAggregations(array $data) {
		$this->aggregations = $data;
	}
	
	/**
	 * Get aggregations from search result
	 *
	 * @return array
	 */
	public function getAggregations() {
		return $this->aggregations;
	}
	
	/**
	 * Get the search params helper class
	 *
	 * @return \ColdTrick\ElasticSearch\SearchParams
	 */
	public function getSearchParams() {
		if (!isset($this->search_params)) {
			$this->search_params = new SearchParams([
				'service' => $this,
			]);
		}
		
		return $this->search_params;
	}
	
	/**
	 * Log the current request to developers log
	 *
	 * @param array $params  search params
	 * @param string $action action name (search, count, etc)
	 *
	 * @return void
	 */
	protected function requestToScreen($params, $action = '') {
		
		$cache = elgg_get_config('log_cache');
		if (empty($cache)) {
			// developer tools log to screen is disabled
			return;
		}
		
		$msg = @json_encode($params, JSON_PRETTY_PRINT);
		
		if ($action) {
			$msg = "{$action}:\n $msg";
		}
		
		$this->logger->notice($msg);
	}
}
