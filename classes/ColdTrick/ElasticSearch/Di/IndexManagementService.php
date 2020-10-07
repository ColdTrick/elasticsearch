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
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Get information about the cluster the client is connected to
	 *
	 * @return false|array
	 */
	public function getClusterInformation() {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			return $this->getClient()->info();
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Check if an index exists with the given name
	 *
	 * @param string $index index name to check
	 *
	 * @return bool
	 */
	public function indexExists(string $index) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			return $this->getClient()->indices()->exists([
				'index' => $index,
			]);
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
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
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Add an alias to an index
	 *
	 * @param string $index the index to add to
	 * @param string $alias the alias
	 *
	 * @return bool
	 */
	public function addAlias(string $index, string $alias) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$response = $this->getClient()->indices()->putAlias([
				'index' => $index,
				'name' => $alias,
			]);
			return elgg_extract('acknowledged', $response, false);
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Get all the aliases of an index
	 *
	 * @param string $index the index to check
	 *
	 * @return false|string[]
	 */
	public function getAliases(string $index) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$response = $this->getClient()->indices()->getAlias([
				'index' => $index,
			]);
			
			$aliases = elgg_extract('aliases', elgg_extract($index, $response, []), []);
			return array_keys($aliases);
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Delete an alias from an index
	 *
	 * @param string $index the index to delete from
	 * @param string $alias the alias
	 *
	 * @return bool
	 */
	public function deleteAlias(string $index, string $alias) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$response = $this->getClient()->indices()->deleteAlias([
				'index' => $index,
				'name' => $alias,
			]);
			return elgg_extract('acknowledged', $response, false);
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Flush an index
	 *
	 * @param string $index index name to flush
	 *
	 * @return bool
	 */
	public function flush(string $index) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$response = $this->getClient()->indices()->flush([
				'index' => $index,
			]);
			
			$failed_shards = elgg_extract('failed', elgg_extract('_shards', $response, []), 0);
			return empty($failed_shards);
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Delete an index
	 *
	 * @param string $index index name to delete
	 *
	 * @return bool
	 */
	public function delete(string $index) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$response = $this->getClient()->indices()->delete([
				'index' => $index,
			]);
			return elgg_extract('acknowledged', $response, false);
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Create a new index
	 *
	 * @param string $index index name
	 *
	 * @return bool
	 */
	public function create(string $index) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		$config = $this->getIndexConfiguration($index);
		
		try {
			$response = $this->getClient()->indices()->create($config);
			return elgg_extract('acknowledged', $response, false);
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Add mapping to an index
	 *
	 * @param string $index index name
	 *
	 * @return bool
	 */
	public function addMapping(string $index) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		$config = $this->getMappingConfiguration($index);
		
		try {
			return $this->getClient()->indices()->putMapping($config);
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Get the index configuration
	 *
	 * @param string $index index name
	 *
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function getIndexConfiguration(string $index) {
		
		$params = [
			'index' => $index,
		];
		
		$return = [
			'index' => $index,
			'body' => [
				'settings' => [
					'analysis' => [
						'analyzer' => [
							'default' => [
								'tokenizer'=> 'standard',
								'filter' => [
									'lowercase',
									'asciifolding',
								],
							],
							'case_insensitive_sort' => [
								'tokenizer' => 'keyword',
								'filter' => [
									'lowercase',
								],
							],
						],
						'normalizer' => [
							'case_insensitive' => [
								'type' => 'custom',
								'filter' => [
									'lowercase',
									'asciifolding',
								],
							],
						],
					],
				],
			],
		];
		
		$return = $this->hooks->trigger('config:index', 'elasticsearch', $params, $return);
		if (!is_array($return)) {
			throw new \InvalidArgumentException(elgg_echo('elasticsearch:index_management:exception:config:index'));
		}
		
		return $return;
	}
	
	/**
	 * Get the mapping configuration
	 *
	 * @param string $index index name
	 *
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function getMappingConfiguration(string $index) {
		
		$params = [
			'index' => $index,
		];
		
		$return = [
			'index' => $index,
			'body' => [
				'dynamic_templates' => [
					[
						'strings' => [
							'match_mapping_type' => 'string',
							'mapping' => [
								'type' => 'text',
								'fields' => [
									'raw' => [
										'type' => 'keyword',
										'normalizer' => 'case_insensitive',
										'ignore_above' => 8191,
									]
								]
							]
						],
					],
					[
						'metadata_strings' => [
							'path_match' => 'metadata.*',
							'mapping' => [
								'type' => 'text',
							],
						],
					],
				],
				'properties' => [
					'name' => [
						'type' => 'text',
						'copy_to' => 'title'
					],
					'description' => [
						'type' => 'text'
					],
					'relationships' => [
						'type' => 'nested',
					],
					'metadata' => [
						'type' => 'nested',
						'include_in_parent' => true,
					],
					'tags' => [
						'type' => 'text',
						'analyzer' => 'case_insensitive_sort',
					],
					'indexed_type' => [
						'type' => 'keyword',
					],
				],
			],
		];
		
		$return = $this->hooks->trigger('config:mapping', 'elasticsearch', $params, $return);
		if (!is_array($return)) {
			throw new \InvalidArgumentException(elgg_echo('elasticsearch:index_management:exception:config:mapping'));
		}
		
		return $return;
	}
}
