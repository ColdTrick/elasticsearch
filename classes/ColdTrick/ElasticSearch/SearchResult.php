<?php
namespace ColdTrick\ElasticSearch;

class SearchResult {
	
	protected $result;
	protected $search_params;
	
	public function __construct($result = [], $search_params) {
		$this->result = $result;
		$this->search_params = $search_params;
	}
	
	public function getResult() {
		return $this->result;
	}
	
	public function getCount() {
		$hits = elgg_extract('hits', $this->result);
		return elgg_extract('total', $hits, 0);
	}
	
	public function toEntities($params) {
		
		$hits = elgg_extract('hits', $this->result);
		$hits = elgg_extract('hits', $hits);
		
		if (!$hits) {
			return [];
		}
		
		$entities = [];
		
		foreach ($hits as $hit) {
			// @todo check if elgg index is used
			$source = elgg_extract('_source', $hit);
		
			$entity = $this->sourceToEntity($source);
				
			if (!$entity) {
				continue;
			}
				
			// set correct search highlighting
			$query = elgg_extract('query', $params);
		
			$title = elgg_extract('title', $source);
			if (!empty($title)) {
				$title = search_get_highlighted_relevant_substrings($title, $query);
				$entity->setVolatileData('search_matched_title', $title);
			}
		
			$desc = elgg_extract('description', $source);
			if (!empty($title)) {
				$desc = search_get_highlighted_relevant_substrings($desc, $query);
				$entity->setVolatileData('search_matched_description', $desc);
			}
				
			$score = elgg_extract('_score', $hit);
			if ($score) {
				$entity->setVolatileData('search_score', $score);
			}
		
			$entities[] = $entity;
		}
		
		return $entities;
	}
	
	protected function sourceToEntity($source) {
		$row = new \stdClass();
		foreach ($source as $key => $value) {
			switch($key) {
				case 'subtype':
					// elastic stores the textual version of the subtype, entity_row_to_elggstar needs the int
					$row->$key = get_subtype_id($source['type'], $value);
					break;
				case 'last_action':
				case 'time_created':
				case 'time_updated':
					// convert the timestamps to unix timestamps
					$value = strtotime($value);
				default:
					$row->$key = $value;
					break;
			}
		}
		
		// enabled attribute is not stored in elasticsearch by default
		$row->enabled = 'yes';
		
		// specials types
		if ($row->type == 'user') {
			// makes sure all attributes are loaded to prevent a db call
			$external_attributes = \ElggUser::getExternalAttributes();
			foreach($external_attributes as $key => $value) {
				if (isset($row->$key)) {
					continue;
				}
		
				$row->$key = $value;
			}
		}
		
		try {
			$result = entity_row_to_elggstar($row);
		} catch (\Exception $e) {
			elgg_log($e->getMessage(), 'NOTICE');
			return false;
		}
		
		return $result;
	}
}