<?php

namespace ColdTrick\ElasticSearch\Di;

use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Elgg\Database\QueryBuilder;
use Elgg\Cli\Progress;

class IndexingService extends BaseClientService {

	/**
	 * @var string[] Supported indexing type
	 */
	const INDEXING_TYPES = [
		'no_index_ts',
		'update',
		'reindex',
	];
	
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
				$guid = (int) elgg_extract('_id', $action['delete']);
				
				if ($status === 200 || $status === 404) {
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
	 * Bulk index documents based on a given type
	 *
	 * @param array $params indexing parameters
	 *  - (string) type: the type of documents to index
	 *  - (int) max_run_time: the maximum number of seconds to spend on the indexing action
	 *
	 * @return bool
	 */
	public function bulkIndexDocuments(array $params) {
		$type = elgg_extract('type', $params);
		if (!in_array($type, self::INDEXING_TYPES)) {
			return false;
		}
		
		return elgg_call(ELGG_IGNORE_ACCESS, function() use ($params) {
			// how long can this task run
			$max_run_time = (int) elgg_extract('max_run_time', $params, 30);
			if (!empty($max_run_time)) {
				set_time_limit($max_run_time + 10);
			} else {
				set_time_limit(0);
			}
			
			// get indexing options
			$type = elgg_extract('type', $params);
			$options = elasticsearch_get_bulk_options($type);
			if (empty($options)) {
				return false;
			}
			
			// max number of entities in one run
			$batch_size = 100;
			$options['limit'] = $batch_size;
			
			// track progress
			$time_left = true;
			$starttime = time();
			$progress = elgg_extract('progress', $params);
			$progress_bar = false;
			if ($progress instanceof Progress) {
				$count = elgg_count_entities($options);
				
				$progress_bar = $progress->start(elgg_echo("elasticsearch:progress:start:{$type}"), $count);
			}
			
			$mark_entity_done = function (\ElggEntity $entity) use ($progress_bar) {
				$entity->setPrivateSetting(ELASTICSEARCH_INDEXED_NAME, time());
				$entity->invalidateCache();
				
				// advance progress bar
				if (!empty($progress_bar)) {
					$progress_bar->advance();
				}
			};
			
			$skip_guids = [];
			$options['wheres'][] = function(QueryBuilder $qb, $main_alias) use (&$skip_guids) {
				if (empty($skip_guids)) {
					return;
				}
				
				return $qb->compare("{$main_alias}.guid", 'NOT IN', $skip_guids);
			};
			
			while ($time_left && ($entities = elgg_get_entities($options))) {
				
				foreach ($entities as $index => $entity) {
					$params = [
						'entity' => $entity,
					];
					
					if ((bool) elgg_trigger_plugin_hook('index:entity:prevent', 'elasticsearch', $params, false)) {
						$mark_entity_done($entity);
						
						unset($entities[$index]);
						continue;
					}
				}
				
				$result = $this->addEntitiesToIndex($entities);
				if (empty($result)) {
					break;
				}
				
				$items = elgg_extract('items', $result);
				foreach ($items as $item) {
					$guid = (int) elgg_extract('_id', elgg_extract('index', $item));
					$status = elgg_extract('status', elgg_extract('index', $item));
					
					$success = ($status >= 200) && ($status < 300);
					if (!$success) {
						$skip_guids[] = $guid;
						
						$error = elgg_extract('error', elgg_extract('index', $item));
						elgg_log("Elasticsearch failed to index {$guid} with error [{$status}][{$error['type']}]: {$error['reason']}", 'WARNING');
						continue;
					}
					
					$entity = get_entity($guid);
					if (!$entity instanceof \ElggEntity) {
						$skip_guids[] = $guid;
						continue;
					}
					
					$mark_entity_done($entity);
				}
				
				if (!empty($max_run_time) && (time() - $starttime) >= $max_run_time) {
					$time_left = false;
					break;
				}
			}
			
			// stop progress bar
			if ($progress instanceof Progress) {
				$progress->finish($progress_bar);
			}
			
			return $time_left;
		});
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
}
