<?php

namespace ColdTrick\ElasticSearch\Di;

use Elgg\Di\ServiceFacade;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elgg\Logger;

class ClientService {

	use ServiceFacade;
	
	/**
	 * @var Client
	 */
	protected $client;
	
	/**
	 * @var Logger
	 */
	protected $logger;
	
	/**
	 * {@inheritDoc}
	 */
	public static function name() {
		return 'elasticsearch.clientservice';
	}
	
	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}
	
	/**
	 * Get the Elasticsearch client
	 *
	 * @return false|\Elasticsearch\Client
	 */
	protected function getClient() {
		if (isset($this->client)) {
			return $this->client;
		}
		
		$this->client = false;
		
		$config = $this->getClientConfig();
		if (empty($config)) {
			return false;
		}
		
		try {
			$this->client = ClientBuilder::fromConfig($config);
		} catch (\Elasticsearch\Common\Exceptions\RuntimeException $e) {
			elgg()->logger;
		}
	}
	
	/**
	 * Get client configuration
	 *
	 * @return false|array
	 */
	protected function getClientConfig() {
		
		$hosts = elgg_get_plugin_setting('host', 'elasticsearch');
		if (empty($hosts)) {
			return false;
		}
		
		$config = [];
		
		// Hostnames
		$hosts = explode(',', $hosts);
		array_walk($hosts, function(&$value) {
			trim($value);
		});
		
		$config['Hosts'] = $hosts;
		
		// SSL verification
		$config['SSLVerification'] = !(bool) elgg_get_plugin_setting('ignore_ssl', 'elasticsearch');
		
		// Logger
		$config['Logger'] = $this->logger;
		
		return $config;
	}
}
