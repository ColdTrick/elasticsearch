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
		
		if ($object instanceof \ElggEntity) {
			self::createEntity($object);
		}
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
		
		if ($object instanceof \ElggEntity) {
			self::deleteEntity($object);
		}
	}
	
	/**
	 * Handle the creation of an ElggEntity
	 *
	 * @param \ElggEntity $entity the entity
	 *
	 * @return void
	 */
	protected static function createEntity(\ElggEntity $entity) {
		
		$client = elasticsearch_get_client();
		if (empty($client)) {
			return;
		}
		
		if (!self::isSearchableEntity($entity)) {
			return;
		}
		
		$client->createDocument($entity->getGUID());
	}
	
	/**
	 * Handle the update of an ElggEntity
	 *
	 * @param \ElggEntity $entity the entity
	 *
	 * @return void
	 */
	protected static function updateEntity(\ElggEntity $entity) {
		
		$client = elasticsearch_get_client();
		if (empty($client)) {
			return;
		}
		
		if (!self::isSearchableEntity($entity)) {
			return;
		}
		
		$client->updateDocument($entity->getGUID());
	}
	
	/**
	 * Handle the deletion of an ElggEntity
	 *
	 * @param \ElggEntity $entity the entity
	 *
	 * @return void
	 */
	protected static function deleteEntity(\ElggEntity $entity) {
		
		$client = elasticsearch_get_client();
		if (empty($client)) {
			return;
		}
		
		$client->deleteDocument($entity->getGUID());
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
