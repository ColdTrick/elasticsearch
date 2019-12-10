<?php

namespace ColdTrick\ElasticSearch;

class EventDispatcher {
	
	/**
	 * Listen to all create events and update Elasticsearch as needed
	 *
	 * @param \Elgg\Event $event 'create', 'all'
	 *
	 * @return void
	 */
	public static function create(\Elgg\Event $event) {
		
		$object = $event->getObject();
		if ($object instanceof \ElggRelationship) {
			self::updateRelationship($object);
		}
		
		self::checkComments($object);
		self::updateEntityForAnnotation($object);
	}
	
	/**
	 * Listen to all update events and update Elasticsearch as needed
	 *
	 * @param \Elgg\Event $event 'update', 'all'
	 *
	 * @return void
	 */
	public static function update(\Elgg\Event $event) {
		
		$object = $event->getObject();
		if ($object instanceof \ElggEntity) {
			self::updateEntity($object);
		}
		
		self::checkComments($object);
		self::updateEntityForAnnotation($object);
	}
	
	/**
	 * Listen to all delete events and update Elasticsearch as needed
	 *
	 * @param \Elgg\Event $event 'delete', 'all'
	 *
	 * @return void
	 */
	public static function delete(\Elgg\Event $event) {
		
		$object = $event->getObject();
		
		// ignore access during cleanup
		elgg_call(ELGG_IGNORE_ACCESS, function() use ($object) {
			
			if ($object instanceof \ElggEntity) {
				self::deleteEntity($object);
			} elseif ($object instanceof \ElggRelationship) {
				self::updateRelationship($object);
			}
			
			self::checkComments($object);
			self::updateEntityForAnnotation($object);
		});
	}
	
	/**
	 * Listen to all disable events and update Elasticsearch as needed
	 *
	 * @param \Elgg\Event $event 'disable', 'all'
	 *
	 * @return void
	 */
	public static function disable(\Elgg\Event $event) {
	
		$object = $event->getObject();
		if ($object instanceof \ElggEntity) {
			self::disableEntity($object);
		}
		
		self::checkComments($object);
		self::updateEntityForAnnotation($object);
	}
	
	/**
	 * Listen to ban user events and update Elasticsearch as needed
	 *
	 * @param \Elgg\Event $event 'ban', 'user'
	 *
	 * @return void
	 */
	public static function banUser(\Elgg\Event $event) {
		
		$user = $event->getObject();
		if (!$user instanceof \ElggUser) {
			return;
		}
		
		// remove user from index
		self::deleteEntity($user);
		
		// remove indexed ts, so when unbanned it will get indexed automatically
		$user->removePrivateSetting(ELASTICSEARCH_INDEXED_NAME);
	}
	
	/**
	 * Updates the entity the annotation is related to
	 *
	 * @param object $annotation the annotation
	 *
	 * @return void
	 */
	protected static function updateEntityForAnnotation($annotation) {
		if (!$annotation instanceof \ElggAnnotation) {
			return;
		}
		
		$entity = $annotation->getEntity();
		if (!$entity instanceof \ElggEntity) {
			return;
		}
		
		self::updateEntity($entity);
	}
	
	/**
	 * Updates parent entities for content that is commented on
	 *
	 * @param object $entity the entity
	 *
	 * @return void
	 */
	protected static function checkComments($entity) {
	
		if (!$entity instanceof \ElggComment) {
			return;
		}
		
		$container_entity = $entity->getContainerEntity();
		if (!$container_entity instanceof \ElggEntity) {
			return;
		}
		
		self::updateEntity($container_entity);
	}
	
	/**
	 * Handle the update of an ElggEntity
	 *
	 * @param \ElggEntity $entity the entity
	 *
	 * @return void
	 */
	protected static function updateEntity(\ElggEntity $entity) {
		
		if (!$entity->getPrivateSetting(ELASTICSEARCH_INDEXED_NAME)) {
			return;
		}
		
		$entity->setPrivateSetting(ELASTICSEARCH_INDEXED_NAME, 0);
	}
	
	/**
	 * Handle the deletion of an ElggEntity
	 *
	 * @param \ElggEntity $entity the entity
	 *
	 * @return void
	 */
	protected static function deleteEntity(\ElggEntity $entity) {

		$last_indexed = $entity->getPrivateSetting(ELASTICSEARCH_INDEXED_NAME);
		if (elgg_is_empty($last_indexed)) {
			return;
		}
		
		$index = elgg_get_plugin_setting('index', 'elasticsearch');
		if (empty($index)) {
			return;
		}
		
		elasticsearch_add_document_for_deletion($entity->guid, [
			'_index' => $index,
			'_id' => $entity->guid,
		]);
	}
	
	/**
	 * Handle the disable of an ElggEntity
	 *
	 * @param \ElggEntity $entity the entity
	 *
	 * @return void
	 */
	protected static function disableEntity(\ElggEntity $entity) {
	
		// remove from index
		self::deleteEntity($entity);

		// remove indexed ts, so when reenabled it will get indexed automatically
		$entity->removePrivateSetting(ELASTICSEARCH_INDEXED_NAME);
	}

	/**
	 * Handle a change of ElggRelationship
	 *
	 * @param \ElggRelationship $relationship the entity
	 *
	 * @return void
	 */
	protected static function updateRelationship(\ElggRelationship $relationship) {
		
		// update entity one
		$entity_guid = $relationship->guid_one;
		
		$entity = get_entity($entity_guid);
		if ($entity instanceof \ElggEntity) {
			self::updateEntity($entity);
		}
		
		// update entity two
		$entity_guid = $relationship->guid_two;
		
		$entity = get_entity($entity_guid);
		if ($entity instanceof \ElggEntity) {
			self::updateEntity($entity);
		}
	}
}
