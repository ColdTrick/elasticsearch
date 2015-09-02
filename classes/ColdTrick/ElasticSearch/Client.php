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
	
	public function __construct($params) {
		
		$this->default_index = elasticsearch_get_setting('index');
		$this->search_alias = elasticsearch_get_setting('search_alias');
		
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
	
	public function getIndex() {
		return $this->default_index;
	}
	
	public function getSearchIndex() {
		if ($this->search_alias) {
			return $this->search_alias;
		}
		
		return $this->default_index;
	}
	
	public function deleteDocument($guid) {
		$params = $this->getDefaultDocumentParams($guid);
		if (empty($params)) {
			return false;
		}
		
		if (!$this->exists($params)) {
			return true;
		}
		
		try {
			return $this->delete($params);
		} catch(\Exception $e) {
			$this->registerErrorForException($e);
			return false;
		}
	}
	
	protected function getDefaultDocumentParams($guid) {
		if (empty($guid)) {
			return;
		}
		
		$entity = get_entity($guid);
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
	
	protected function getDocumentTypeFromEntity(\ElggEntity $entity) {
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
		
		$entity = get_entity($guid);
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
		
		$json_data = json_decode($message, true);
		if (is_array($json_data) && isset($json_data['error'])) {
			$message = $json_data['error'];
		}
		
		register_error($message);
	}
	
	/**
	 * Hook to adjust exportable values of basic entities for search
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function entityToObject($hook, $type, $returnvalue, $params) {
		if (!elgg_in_context('search:index')) {
			return;
		}
		
		$entity = elgg_extract('entity', $params);
		if (!$entity) {
			return;
		}
		
		// add some extra values to be submitted to the search index
		$returnvalue->last_action = date('c', $entity->last_action);
		$returnvalue->access_id = $entity->access_id;
	}
	
	protected function requestToScreen($params, $action = '') {
		
		$cache = elgg_get_config('log_cache');
		if (empty($cache)) {
			return;
		}
		
		$msg = @json_encode($params);
		
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
}
