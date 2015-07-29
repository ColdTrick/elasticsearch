<?php

namespace ColdTrick\ElasticSearch;

class Client extends \Elasticsearch\Client {
	
	protected $default_index;
	
	public function __construct($params) {
		
		$this->default_index = elgg_get_plugin_setting('index', 'elasticsearch');
		
		return parent::__construct($params);
	}
	
	public function search($params = array()) {
		
		if (!isset($params['index'])) {
			$params['index'] = $this->default_index;
		}
		
		try {
			return $this->search($params);
		} catch(\Exception $e) {
			$this->registerErrorForException($e);
			return false;
		}
	}
	
	public function createDocument($guid) {
		$params = $this->getDefaultDocumentParams($guid);
		if (empty($params)) {
			return false;
		}
		
		$params['body'] = $this->getBodyFromEntity($guid);
		
		try {
			return $this->create($params);
		} catch(\Exception $e) {
			$this->registerErrorForException($e);
			return false;
		}
	}
	
	public function updateDocument($guid) {
		$params = $this->getDefaultDocumentParams($guid);
		if (empty($params)) {
			return false;
		}
		
		if (!$this->exists($params)) {
			return $this->createDocument($guid);
		}
		
		$params['body'] = $this->getBodyFromEntity($guid);
		
		try {
			return $this->index($params);
		} catch(\Exception $e) {
			$this->registerErrorForException($e);
			return false;
		}
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
	
	private function getDefaultDocumentParams($guid) {
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
	
	private function getDocumentTypeFromEntity(\ElggEntity $entity) {
		$type = $entity->getType();
		$subtype = $entity->getSubType();
		
		if (empty($subtype)) {
			return "$type";
		}
		
		return "$type.$subtype";
	}

	private function getBodyFromEntity($guid) {
		if (empty($guid)) {
			return;
		}
		
		$entity = get_entity($guid);
		if (!$entity) {
			return;
		}
		
		$result = (array) $entity->toObject();
		
		return $result;
	}
	
	private function registerErrorForException(\Exception $e) {
		$message = $e->getMessage();
		
		$json_data = json_decode($message, true);
		if (is_array($json_data) && isset($json_data['error'])) {
			$message = $json_data['error'];
		}
		
		register_error($message);
	}
}
