<?php

namespace ColdTrick\ElasticSearch;

class Export {
	
	/**
	 * Hook to adjust exportable values of basic entities for search
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function entityToObject($hook, $type, $returnvalue, $params) {
		if (!elgg_in_context('search:index')) {
			return;
		}
	
		$entity = elgg_extract('entity', $params);
		if (!$entity) {
			return;
		}
	
		// add some extra values to be submitted to the search index
		$returnvalue->last_action = date('c', $entity->last_action);
		$returnvalue->access_id = $entity->access_id;
	}
	
	/**
	 * Hook to export relationship entities for search
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function entityRelationshipsToObject($hook, $type, $returnvalue, $params) {
		if (!elgg_in_context('search:index')) {
			return;
		}
	
		$entity = elgg_extract('entity', $params);
		if (!$entity) {
			return;
		}
	
		$relationships = get_entity_relationships($entity->getGUID());
		if (empty($relationships)) {
			return;
		}
	
		$result = [];
		foreach ($relationships as $relationship) {
			$result[] = [
				'id' => (int) $relationship->id,
				'time_created' => date('c', $relationship->time_created),
				'guid_one' => (int) $relationship->guid_one,
				'guid_two' => (int) $relationship->guid_two,
				'relationship' => $relationship->relationship,
			];
		}
	
		$returnvalue->relationships = $result;
	}
	
	/**
	 * Hook to extend the indexable entity types/subtypes
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param array  $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void|array
	 */
	public static function indexEntityTypeSubtypes($hook, $type, $returnvalue, $params) {
		
		if (empty($returnvalue)) {
			return;
		}
		
		$objects = elgg_extract('object', $returnvalue);
		if (empty($objects)) {
			return;
		}
		
		// make sure page and page_top are present
		if (in_array('page', $objects) || in_array('page_top', $objects)) {
			$objects[] = 'page';
			$objects[] = 'page_top';
		}
		
		// add discussion replies
		if (in_array('groupforumtopic', $objects)) {
			$objects[] = 'discussion_reply';
		}
		
		$objects = array_unique($objects);
		$returnvalue['object'] = $objects;
		
		return $returnvalue;
	}
}