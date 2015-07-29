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
			$result = parent::search($params);
		} catch(\Exception $e) {
			$this->registerErrorForException($e);
			return false;
		}
		
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
