<?php

namespace ColdTrick\ElasticSearch\Di;

use Elgg\Di\ServiceFacade;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elgg\Logger;
use Elasticsearch\Common\Exceptions\ElasticsearchException;

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
	 * Get information about the index status
	 *
	 * @return false|array
	 */
	public function getIndexStatus() {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$status = $this->getClient()->indices()->stats();
			
			return elgg_extract('indices', $status, false);
		} catch (ElasticsearchException $e) {
			$this->logger->notice($e);
		}
		
		return false;
	}
	
	/**
	 * Check if an index has the given alias
	 *
	 * @param string $index the index to check
	 * @param string $alias the alias
	 *
	 * @return bool
	 */
	public function indexHasAlias(string $index, string $alias) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			return $this->getClient()->indices()->existsAlias([
				'index' => $index,
				'name' => $alias,
			]);
		} catch (ElasticsearchException $e) {
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
