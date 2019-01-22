<?php

namespace ColdTrick\ElasticSearch;

class Client extends \Elasticsearch\Client {
	
	/**
	 * The default index to use
	 *
	 * @var string
	 */
	protected $default_index;
	
	/**
	 * The default search index alias to use
	 *
	 * @var string
	 */
	protected $search_alias;
	
	/**
	 * The suggestions
	 *
	 * @var array
	 */
	protected $suggestions;
	
	/**
	 * The search params
	 *
	 * @var \ColdTrick\ElasticSearch\SearchParams
	 */
	public $search_params;
	
	public function __construct($params) {
		
		$this->default_index = elasticsearch_get_setting('index');
		$this->search_alias = elasticsearch_get_setting('search_alias');
		
		$this->search_params = new SearchParams(['client' => $this]);
		
		parent::__construct($params);
	}
	
	public function search($params = array()) {
		
		if (!isset($params['index'])) {
			$params['index'] = $this->getSearchIndex();
		}
		
		$this->requestToScreen($params, 'SEARCH');
		
		try {
			return parent::search($params);
		} catch(\Exception $e) {
			$this->registerErrorForException($e);
			return array();
		}
	}
	
	public function suggest($params = []) {
		if (!isset($params['index'])) {
			$params['index'] = $this->getSearchIndex();
		}

		$this->requestToScreen($params, 'SUGGEST');
		
		try {
			return parent::suggest($params);
		} catch(\Exception $e) {
			$this->registerErrorForException($e);
			return array();
		}
	}

	public function count($params = []) {
		if (!isset($params['index'])) {
			$params['index'] = $this->getSearchIndex();
		}

		$this->requestToScreen($params, 'COUNT');
		
		try {
			return parent::count($params);
		} catch(\Exception $e) {
			$this->registerErrorForException($e);
			return array();
		}
	}
	
	public function indexDocument($guid) {
		$params = $this->getDefaultDocumentParams($guid);
		if (empty($params)) {
			return false;
		}
		
		$params['body'] = $this->getBodyFromEntity($guid);
		
		try {
			return $this->index($params);
		} catch(\Exception $e) {
			$this->registerErrorForException($e);
			return false;
		}
	}
	
	public function bulkIndexDocuments($guids = array()) {
		if (!is_array($guids)) {
			return false;
		}
		
		if (empty($guids)) {
			return $guids;
		}
		
		$params = [];
		foreach ($guids as $guid) {
			$doc_params = $this->getDefaultDocumentParams($guid);
			if (empty($doc_params)) {
				continue;
			}
					
			$params['body'][] = array(
				'index' => array(
					'_index' => $doc_params['index'],
					'_type' => $doc_params['type'],
					'_id' => $doc_params['id']
				)
			);
			

			$params['body'][] = $this->getBodyFromEntity($guid);
		}
		
		if (empty($params)) {
			return false;
		}
		
		try {
			return $this->bulk($params);
		} catch(\Exception $e) {
			$this->registerErrorForException($e);
			return false;
		}
	}
	
	/**
	 * Deletes documents in bulk from index
	 *
	 * @return void|array
	 */
	public function bulkDeleteDocuments() {
		
		$documents = elasticsearch_get_documents_for_deletion();
		if (empty($documents)) {
			return;
		}
		
		$params = [];
		foreach ($documents as $document) {
			$params['body'][] = ['delete' => $document];
		}

		try {
			$result = $this->bulk($params);
			
			if (!empty($result)) {
				
				$items = elgg_extract('items', $result);
				foreach ($items as $action) {
					
					$status = elgg_extract('status', $action['delete']);
					$found = elgg_extract('found', $action['delete']);
					$guid = (int) elgg_extract('_id', $action['delete']);
					
					if (($status === 200 && $found) || ($status === 404 && !$found)) {
						// document was removed
						elasticsearch_remove_document_for_deletion($guid);
					} else {
						// some error occured, reschedule delete
						elasticsearch_reschedule_document_for_deletion($guid);
					}
				}
			}
			
			return $result;
		} catch(\Exception $e) {
			$this->registerErrorForException($e);
			return false;
		}
		
	}
	
	public function getIndex() {
		return $this->default_index;
	}
	
	public function getSearchIndex() {
		if ($this->search_alias) {
			return $this->search_alias;
		}
		
		return $this->default_index;
	}
		
	protected function getDefaultDocumentParams($guid) {
		if (empty($guid)) {
			return;
		}
	
		try {
			$entity = get_entity($guid);
		} catch (\Exception $e) {
			return;
		}
		
		if (!$entity) {
			return;
		}
		
		$params = [
			'id' => $guid,
			'index' => $this->default_index,
			'type' => $this->getDocumentTypeFromEntity($entity),
		];
		
		return $params;
	}
	
	public function getDocumentTypeFromEntity(\ElggEntity $entity) {
		$type = $entity->getType();
		$subtype = $entity->getSubType();
		
		if (empty($subtype)) {
			return "$type";
		}
		
		return "$type.$subtype";
	}

	protected function getBodyFromEntity($guid) {
		if (empty($guid)) {
			return;
		}
		
		try {
			$entity = get_entity($guid);
		} catch (\Exception $e) {
			return;
		}
		
		if (!$entity) {
			return;
		}
		
		elgg_push_context('search:index');
		$result = (array) $entity->toObject();
		elgg_pop_context();

		return $result;
	}
	
	protected function registerErrorForException(\Exception $e) {
		$message = $e->getMessage();
		
		$json_data = @json_decode($message, true);
		if (is_array($json_data) && isset($json_data['error'])) {
			$message = $json_data['error'];
		}
		
		elgg_log($message, 'ERROR');
		
		register_error(elgg_echo('elasticsearch:error:search'));
	}
	
	protected function requestToScreen($params, $action = '') {
		
		$cache = elgg_get_config('log_cache');
		if (empty($cache)) {
			return;
		}
		
		$msg = @json_encode($params);
		$msg = htmlentities($msg, ENT_QUOTES, 'UTF-8');
		
		if ($action) {
			$msg = "$action: $msg";
		}
		
		$cache->insertDump('', '', true, ['msg' => $msg]);
	}
	
	public function setSuggestions($data) {
		$this->suggestions = $data;
	}
	
	public function getSuggestions() {
		return $this->suggestions;
	}
	
	/**
	 * Inspect a GUID in Elasticsearch
	 *
	 * @param int $guid the GUID to inspect
	 *
	 * @return false|array
	 */
	public function inspect($guid, $return_raw = false) {
		$guid = (int) $guid;
		if ($guid < 1) {
			return false;
		}
		
		$search_params = [
			'index' => $this->getIndex(),
			'body' => [
				'query' => [
					'filtered' => [
						'filter' => [
							'term' => [
								'guid' => $guid,
							],
						],
					],
				],
			],
		];
		try {
			$search_result = $this->search($search_params);
			
			$s = new SearchResult($search_result, $search_params);
			$hit = $s->getHit($guid);
			if (empty($hit)) {
				return false;
			}
			
			if ((bool) $return_raw) {
				return $hit;
			}
			
			return elgg_extract('_source', $hit);
		} catch (\Exception $e) {
			// somethig went wrong
		}
		
		return false;
	}
}
