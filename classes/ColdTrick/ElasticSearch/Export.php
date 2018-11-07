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
	 * @return \stdClass
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
		$returnvalue->indexed_type = self::getEntityIndexType($entity);
		
		return $returnvalue;
	}
	
	/**
	 * Get the type under which the entity will be indexed
	 *
	 * Defaults to 'type.subtype'
	 *
	 * @param \ElggEntity $entity the entity to index
	 *
	 * @return string
	 */
	protected static function getEntityIndexType(\ElggEntity $entity) {
		$parts = [
			$entity->getType(),
			$entity->getSubtype(),
		];
		
		$parts = array_filter($parts);
		
		$index_type = implode('.', $parts);
		
		$params = [
			'entity' => $entity,
			'default' => $index_type,
		];
		
		return elgg_trigger_plugin_hook('index:entity:type', 'elasticsearch', $params, $index_type);
	}

	/**
	 * Hook to export entity metadata for search
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function entityMetadataToObject($hook, $type, $returnvalue, $params) {
		
		if (!elgg_in_context('search:index')) {
			return;
		}
	
		$entity = elgg_extract('entity', $params);
		if (!$entity instanceof \ElggEntity) {
			return;
		}
		
		$defaults = [];
		switch ($entity->getType()) {
			case 'user':
				$defaults[] = 'name';
				$defaults[] = 'username';
				$defaults[] = 'language';
				break;
			case 'object':
				$defaults[] = 'title';
				$defaults[] = 'description';
				break;
			case 'group':
			case 'site':
				$defaults[] = 'name';
				$defaults[] = 'description';
				break;
		}
		
		$metadata_names = elgg_trigger_plugin_hook('export:metadata_names', 'elasticsearch', $params, $defaults);
		if (empty($metadata_names)) {
			return;
		}
		
		$metadata = elgg_get_metadata([
			'guid' => $entity->getGUID(),
			'metadata_names' => $metadata_names,
			'limit' => false,
		]);
		
		if (empty($metadata)) {
			return;
		}
		
		$result = [];
		foreach ($metadata as $data) {
			
			if (elgg_is_empty($data)) {
				continue;
			}
			
			if (!isset($result[$data->name])) {
				$result[$data->name] = [];
			}
			
			$result[$data->name][] = $data->value;
		}
		
		$returnvalue->metadata = $result;
		
		return $returnvalue;
	}

	/**
	 * Hook to join user/group profile tag fields with tags
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function profileTagFieldsToTags($hook, $type, $returnvalue, $params) {
		
		if (!elgg_in_context('search:index')) {
			return;
		}
	
		$entity = elgg_extract('entity', $params);
		if (!$entity) {
			return;
		}
		
		if (!in_array($entity->getType(), ['user', 'group'])) {
			return;
		}
		
		if ($entity instanceof \ElggUser) {
			$profile_fields = elgg_get_config('profile_fields');
		} elseif ($entity instanceof \ElggGroup) {
			$profile_fields = elgg_get_config('group');
		}
		
		if (empty($profile_fields)) {
			return;
		}
		
		$tags = [];
		foreach ($profile_fields as $field_name => $type) {
			if ($type !== 'tags') {
				continue;
			}

			$field_tags = (array) $entity->$field_name;
			if (!empty($field_tags)) {
				$tags = array_merge($tags, $field_tags);
			}
		}
		
		if (empty($tags)) {
			return;
		}
		
		if (isset($returnvalue->tags)) {
			$current_tags = (array) $returnvalue->tags;
			$tags = array_merge($current_tags, $tags);
		}
		
		// make all lowercase (for better uniqueness)
		$tags = array_map('strtolower', $tags);
		// make unique
		$tags = array_unique($tags);
		// reset array indexes
		$tags = array_values($tags);
		
		// make them unique
		$returnvalue->tags = $tags;
		
		return $returnvalue;
	}

	/**
	 * Hook to export entity counters for search
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function entityCountersToObject($hook, $type, $returnvalue, $params) {
		
		if (!elgg_in_context('search:index')) {
			return;
		}
	
		$entity = elgg_extract('entity', $params);
		if (!$entity) {
			return;
		}
		
		$counters = elgg_trigger_plugin_hook('export:counters', 'elasticsearch', $params, []);
		if (empty($counters)) {
			return;
		}
		
		$returnvalue->counters = $counters;
		
		return $returnvalue;
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
		
		if (!isset($returnvalue->relationships)) {
			$returnvalue->relationships = $result;
		} elseif (is_array($returnvalue->relationships)) {
			$returnvalue->relationships = array_merge($returnvalue->relationships, $result);
		}
		
		return $returnvalue;
	}
	
	/**
	 * Hook to strip tags from selected entity fields
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param string $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void
	 */
	public static function stripTags($hook, $type, $returnvalue, $params) {
		
		if (!elgg_in_context('search:index')) {
			return;
		}
	
		$fields = ['title', 'name', 'description'];
		
		foreach ($fields as $field) {
			
			if (!isset($returnvalue->$field)) {
				continue;
			}
			
			$curval = $returnvalue->$field;
			if (empty($curval)) {
				continue;
			}
			
			$curval = html_entity_decode($curval, ENT_QUOTES, 'UTF-8');
			
			$returnvalue->$field = elgg_strip_tags($curval);
		}
		
		return $returnvalue;
	}
	
	/**
	 * Hook to extend the exportable metadata names
	 *
	 * @param string $hook        the name of the hook
	 * @param string $type        the type of the hook
	 * @param array  $returnvalue current return value
	 * @param array  $params      supplied params
	 *
	 * @return void|array
	 */
	public static function exportProfileMetadata($hook, $type, $returnvalue, $params) {
		
		if (!is_array($returnvalue)) {
			return;
		}
		
		$entity = elgg_extract('entity', $params);
		if (!$entity) {
			return;
		}
		
		$config_field = '';
		if ($entity instanceof \ElggUser) {
			$config_field = 'profile_fields';
		} elseif ($entity instanceof \ElggGroup) {
			$config_field = 'group';
		}
		
		if (empty($config_field)) {
			return;
		}
		
		$profile_fields = elgg_get_config($config_field);
		$field_names = array_keys($profile_fields);
				
		return array_merge($returnvalue, $field_names);
	}
	
	/**
	 * Hook to export group members count
	 *
	 * @param \Elgg\Hook $hook 'export:counters', 'elasticsearch'
	 *
	 * @return void|array
	 */
	public static function exportGroupMemberCount(\Elgg\Hook $hook) {
		
		$entity = $hook->getEntityParam();
		if (!$entity instanceof \ElggGroup) {
			return;
		}
		
		$return = $hook->getValue();
		
		$return['member_count'] = elgg_call(ELGG_IGNORE_ACCESS, function() use ($entity) {
			return $entity->getMembers(['count' => true]);
		});
		
		return $return;
	}
	
	/**
	 * Hook to export likes count
	 *
	 * @param \Elgg\Hook $hook 'export:counters', 'elasticsearch'
	 *
	 * @return void|array
	 */
	public static function exportLikesCount(\Elgg\Hook $hook) {
		
		if (!elgg_is_active_plugin('likes')) {
			return;
		}
		
		$entity = $hook->getEntityParam();
		if (!$entity instanceof \ElggEntity) {
			return;
		}
		
		if (!(bool) elgg_trigger_plugin_hook('likes:is_likable', "{$entity->getType()}:{$entity->getSubtype()}", [], false)) {
			$count = 0;
		} else {
			$count = elgg_call(ELGG_IGNORE_ACCESS, function () use ($entity) {
				return likes_count($entity);
			});
		}
		
		$return = $hook->getValue();
		
		$return['likes'] = $count;
		
		return $return;
	}
	
	/**
	 * Hook to export comments count
	 *
	 * @param \Elgg\Hook $hook 'export:counters', 'elasticsearch'
	 *
	 * @return void|array
	 */
	public static function exportCommentsCount(\Elgg\Hook $hook) {
		
		$entity = $hook->getEntityParam();
		if (!$entity instanceof \ElggEntity) {
			return;
		}
		
		$return = $hook->getValue();
		
		$return['comments'] = elgg_call(ELGG_IGNORE_ACCESS, function() use ($entity) {
			return $entity->countComments();
		});
		
		return $return;
	}
}
