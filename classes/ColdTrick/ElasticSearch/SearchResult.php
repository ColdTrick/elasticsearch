<?php

namespace ColdTrick\ElasticSearch;

class SearchResult {
	
	/**
	 * @var array
	 */
	protected $result;
	
	/**
	 * @var array
	 */
	protected $search_params;
	
	public function __construct($result = [], $search_params) {
		$this->result = $result;
		$this->search_params = $search_params;
	}
	
	/**
	 * Get search results
	 *
	 * @return array
	 */
	public function getResult() {
		return $this->result;
	}
	
	/**
	 * Get search result count
	 *
	 * @return int
	 */
	public function getCount() {
		
		$hits = elgg_extract('hits', $this->result);
		if ($hits !== null) {
			return elgg_extract('total', $hits, 0);
		}

		return elgg_extract('count', $this->result, 0);
	}
	
	/**
	 * Get search hits
	 *
	 * @return array
	 */
	public function getHits() {
		$hits = elgg_extract('hits', $this->result, []);
		
		return elgg_extract('hits', $hits);
	}
	
	/**
	 * Get a single hit from the results
	 *
	 * @param int $id the id in Elasticsearch (usualy an Elgg GUID)
	 *
	 * @return false|array
	 */
	public function getHit($id) {
		
		$hits = $this->getHits();
		if (empty($hits)) {
			return false;
		}
		
		foreach ($hits as $hit) {
			$_id = (int) elgg_extract('_id', $hit);
			if ($id === $_id) {
				return $hit;
			}
		}
		
		return false;
	}
	
	/**
	 * Get aggregations
	 *
	 * @return array
	 */
	public function getAggregations() {
		return elgg_extract('aggregations', $this->result);
	}
	
	/**
	 * Convert search results to entities
	 *
	 * @param array $params additional params
	 *
	 * @return \ElggEntity[]
	 */
	public function toEntities($params) {
		
		$hits = $this->getHits();
		
		if (empty($hits)) {
			return [];
		}
		
		$entities = [];
		
		foreach ($hits as $hit) {
			$params = [
				'hit' => $hit,
				'search_params' => $this->search_params,
			];
			
			$hit = elgg_trigger_plugin_hook('to:entity:before', 'elasticsearch', $params, $hit);
			$params['hit'] = $hit;
			
			$entity = elgg_trigger_plugin_hook('to:entity', 'elasticsearch', $params, null);
			if (!$entity instanceof \ElggEntity) {
				continue;
			}
			
			$source = elgg_extract('_source', $hit);
			
			// set correct search highlighting
			$highlight = (array) elgg_extract('highlight', $hit, []);
			
			// title
			$highlight_title = '';
			$title = elgg_extract('title', $highlight);
			if (!empty($title)) {
				if (is_array($title)) {
					$title = implode('', $title);
				}
				$highlight_title = $title;
			}
						
			// no title found
			if (empty($highlight_title)) {
				$highlight_title = elgg_extract('title', $source);
			}
			$entity->setVolatileData('search_matched_title', $highlight_title);
			
			// description
			$desc = elgg_extract('description', $highlight);
			if (empty($desc)) {
				$desc = elgg_get_excerpt(elgg_extract('description', $source));
			}
			if (is_array($desc)) {
				$desc = implode('...', $desc);
			}
			$entity->setVolatileData('search_matched_description', $desc);
			
			// tags
			$tags = elgg_extract('tags', $highlight);
			if (!empty($tags)) {
				if (is_array($tags)) {
					$tags = implode(', ', $tags);
				}
				
				$label = elgg_format_element('strong', [
					'class' => 'search-match-extra-label',
				], elgg_echo('tags'));
				
				// format output
				$tags = elgg_format_element('p', [
					'class' => 'elgg-output search-match-extra',
				], $label . ': ' . $tags);
				
				$entity->setVolatileData('search_matched_extra', $tags);
			}
			
			// score
			$score = elgg_extract('_score', $hit);
			if ($score) {
				$entity->setVolatileData('search_score', $score);
			}
			
			$entities[] = $entity;
		}
		
		return $entities;
	}
	
	/**
	 * get the GUIDs of all re results
	 *
	 * return []
	 */
	public function toGuids() {
		
		$hits = $this->getHits();
		if (empty($hits)) {
			return [];
		}
		
		$guids = [];
		
		foreach ($hits as $hit) {
			
			$source = elgg_extract('_source', $hit);
			if (empty($source)) {
				continue;
			}
			
			$guid = elgg_extract('guid', $source);
			if (empty($guid)) {
				continue;
			}
			
			$guids[] = $guid;
		}
		
		return $guids;
	}
}
