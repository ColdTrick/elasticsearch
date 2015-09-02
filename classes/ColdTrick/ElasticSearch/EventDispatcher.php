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
		
		elasticsearch_add_document_for_deletion($entity->getGUID(), [
			'_index' => $client->getIndex(),
			'_type' => $client->getDocumentTypeFromEntity($entity),
			'_id' => $entity->getGUID(),
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
