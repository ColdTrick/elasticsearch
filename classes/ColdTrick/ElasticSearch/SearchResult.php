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
		if ($hits !== null) {
			return elgg_extract('total', $hits, 0);
		}

		return elgg_extract('count', $this->result, 0);
	}
	
	public function getHits() {
		$hits = elgg_extract('hits', $this->result);
		return elgg_extract('hits', $hits);
	}
	
	public function getSuggestions() {
		return elgg_extract('suggest', $this->result);
	}
	
	public function toEntities($params) {
		
		$hits = $this->getHits();
		
		if (!$hits) {
			return [];
		}
		
		$entities = [];
		
		foreach ($hits as $hit) {
			$source = elgg_extract('_source', $hit);
		
			$entity = elgg_trigger_plugin_hook('to:entity', 'elasticsearch', ['hit' => $hit, 'search_params' => $this->search_params], null);
				
			if (!($entity instanceof \ElggEntity)) {
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
}