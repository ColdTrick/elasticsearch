<?php

namespace ColdTrick\ElasticSearch\Di;

use Elgg\Di\ServiceFacade;
use Elasticsearch\Client;
use Elgg\Logger;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Elasticsearch\ClientBuilder;
use Elgg\PluginHooksService;

abstract class BaseClientService {

	use ServiceFacade;
	
	/**
	 * @var Client
	 */
	private $client;
	
	/**
	 * @var Logger
	 */
	protected $logger;
	
	/**
	 * @var PluginHooksService
	 */
	protected $hooks;
	
	public function __construct(Logger $logger, PluginHooksService $hooks) {
		$this->logger = $logger;
		$this->hooks = $hooks;
	}
	
	/**
	 * Is the client ready for use
	 *
	 * @return bool
	 */
	public function isClientReady() {
		return !empty($this->getClient());
	}
	
	/**
	 * Are the Elasticsearch servers reachable
	 *
	 * @return bool
	 */
	public function ping() {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			return $this->getClient()->ping();
		} catch (ElasticsearchException $e) {
			// no need to log
			$this->logger->notice($e);
		}
		
		return false;
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
			$this->logger->error($e);
		}
		
		return $this->client;
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
			$value = trim($value);
		});
		array_walk($hosts, function(&$value) {
			$value = rtrim($value, '/');
		});
		
		$config['Hosts'] = $hosts;
		
		// SSL verification
		$config['SSLVerification'] = !(bool) elgg_get_plugin_setting('ignore_ssl', 'elasticsearch');
		
		// Logger
		$config['Logger'] = $this->logger;
		
		return $config;
	}
}
