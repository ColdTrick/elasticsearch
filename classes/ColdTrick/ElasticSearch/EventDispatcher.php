<?php

namespace ColdTrick\ElasticSearch;

class EventDispatcher {
	
	/**
	 * Listen to all create events and update Elasticsearch as needed
	 *
	 * @param string        $event  the name of the event
	 * @param string        $type   the type of the event
	 * @param \ElggExtender $object the affected content
	 *
	 * @return void
	 */
	public static function create($event, $type, $object) {
		if ($object instanceof \ElggRelationship) {
			self::updateRelationship($object);
		}
		
		self::checkComments($object);
		self::updateEntityForAnnotation($object);
	}
	
	/**
	 * Listen to all update events and update Elasticsearch as needed
	 *
	 * @param string        $event  the name of the event
	 * @param string        $type   the type of the event
	 * @param \ElggExtender $object the affected content
	 *
	 * @return void
	 */
	public static function update($event, $type, $object) {
		
		if ($object instanceof \ElggEntity) {
			self::updateEntity($object);
		}
		
		self::checkComments($object);
		self::updateEntityForAnnotation($object);
	}
	
	/**
	 * Listen to all delete events and update Elasticsearch as needed
	 *
	 * @param string        $event  the name of the event
	 * @param string        $type   the type of the event
	 * @param \ElggExtender $object the affected content
	 *
	 * @return void
	 */
	public static function delete($event, $type, $object) {
		
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
	 * @param string        $event  the name of the event
	 * @param string        $type   the type of the event
	 * @param \ElggExtender $object the affected content
	 *
	 * @return void
	 */
	public static function disable($event, $type, $object) {
	
		if ($object instanceof \ElggEntity) {
			self::disableEntity($object);
		}
		
		self::checkComments($object);
		self::updateEntityForAnnotation($object);
	}
	
	/**
	 * Listen to ban user events and update Elasticsearch as needed
	 *
	 * @param string    $event the name of the event
	 * @param string    $type  the type of the event
	 * @param \ElggUser $user  the affected user
	 *
	 * @return void
	 */
	public static function banUser($event, $type, $user) {
		
		if (!($user instanceof \ElggUser)) {
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
		if (!($annotation instanceof \ElggAnnotation)) {
			return;
		}
		
		$entity_guid = $annotation->entity_guid;
		$entity = get_entity($entity_guid);
		if (!$entity) {
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
		if (!$container_entity) {
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

		if (!$entity->getPrivateSetting(ELASTICSEARCH_INDEXED_NAME)) {
			return;
		}
		
		$client = elasticsearch_get_client();
		if (empty($client)) {
			return;
		}
		
		elasticsearch_add_document_for_deletion($entity->guid, [
			'_index' => $client->getIndex(),
			'_type' => 'entities',
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
	
		$client = elasticsearch_get_client();
		if (empty($client)) {
			return;
		}
	
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
		if ($entity) {
			self::updateEntity($entity);
		}
		
		// update entity two
		$entity_guid = $relationship->guid_two;
		
		$entity = get_entity($entity_guid);
		if ($entity) {
			self::updateEntity($entity);
		}
	}
	
	/**
	 * Check if the given entity is searchable (and needs to be in Elasticsearch)
	 *
	 * @param \ElggEntity $entity the entity
	 *
	 * @return bool
	 */
	protected static function isSearchableEntity(\ElggEntity $entity) {
		
		if (empty($entity) || !($entity instanceof \ElggEntity)) {
			return false;
		}
		
		$type = $entity->getType();
		$type_subtypes = elasticsearch_get_registered_entity_types();
		if (!isset($type_subtypes[$type])) {
			return false;
		}
		
		$subtype = $entity->getSubtype();
		if (empty($subtype)) {
			// eg. user, group, site
			return true;
		}
		
		return in_array($subtype, $type_subtypes[$type]);
	}
}
