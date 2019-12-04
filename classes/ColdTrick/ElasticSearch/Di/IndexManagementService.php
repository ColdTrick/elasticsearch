<?php

namespace ColdTrick\ElasticSearch\Di;

use Elasticsearch\Common\Exceptions\ElasticsearchException;

class IndexManagementService extends BaseClientService {

	/**
	 * {@inheritDoc}
	 */
	public static function name() {
		return 'elasticsearch.indexmanagementservice';
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
}
