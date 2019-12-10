<?php

namespace ColdTrick\ElasticSearch\Di;

use Elasticsearch\Common\Exceptions\ElasticsearchException;

class IndexingService extends BaseClientService {

	/**
	 * @var false|string
	 */
	protected $index;
	
	/**
	 * {@inheritDoc}
	 */
	public static function name() {
		return 'elasticsearch.indexingservice';
	}
	
	/**
	 * Add or update entities in the Elasticsearch index
	 *
	 * @param array $entities an array of \ElggEntity or of guids
	 *
	 * @return false|array
	 */
	public function addEntitiesToIndex(array $entities = []) {
		
		if (empty($entities) || !$this->isClientReady()) {
			return false;
		}
		
		$params = [
			'body' => [],
		];
		foreach ($entities as $entity) {
			if (is_numeric($entity)) {
				// also able to provide guids
				$entity = get_entity($entity);
			}
			
			if (!$entity instanceof \ElggEntity) {
				continue;
			}
			
			// Set basic entity information for indexing
			$params['body'][] = [
				'index' => [
					'_index' => $this->getIndex(),
					'_id' => $entity->guid,
				],
			];
			
			// get full entity information to put into index
			$params['body'][] = $this->getBodyFromEntity($entity);
		}
		
		if (empty($params)) {
			return false;
		}
		
		try {
			return $this->getClient()->bulk($params);
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Deletes documents in bulk from index
	 *
	 * @return bool
	 */
	public function bulkDeleteDocuments() {
		
		if (!$this->isClientReady()) {
			return false;
		}
		
		$documents = elasticsearch_get_documents_for_deletion();
		if (empty($documents)) {
			// nothing to delete
			return true;
		}
		
		$params = [
			'body' => [],
		];
		foreach ($documents as $document) {
			$params['body'][] = ['delete' => $document];
		}
		
		try {
			$result = $this->getClient()->bulk($params);
			if (empty($result)) {
				return false;
			}
			
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
			
			return true;
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Get body (data) for indexing of an entity
	 *
	 * @param \ElggEntity $entity entity
	 *
	 * @return array
	 */
	protected function getBodyFromEntity(\ElggEntity $entity) {
		
		elgg_push_context('search:index');
		
		$result = (array) $entity->toObject();
		
		elgg_pop_context();
		
		return $result;
	}
	
	/**
	 * Get the name of the index that holds all information
	 *
	 * @return false|string
	 */
	protected function getIndex() {
		if (isset($this->index)) {
			return $this->index;
		}
		
		$this->index = false;
		
		$index = elgg_get_plugin_setting('index', 'elasticsearch');
		if (!empty($index)) {
			$this->index = $index;
		}
		
		return $this->index;
	}
}
