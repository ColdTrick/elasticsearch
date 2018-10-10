<?php

namespace ColdTrick\ElasticSearch;

use Elgg\DefaultPluginBootstrap;

class Bootstrap extends DefaultPluginBootstrap {
	
	/**
	 * {@inheritDoc}
	 */
	public function init() {
		
		// ajax views
		elgg_register_ajax_view('elasticsearch/logging/view');
		
		// view extends
		elgg_extend_view('forms/search_advanced/search', 'elasticsearch/search/suggest', 800);
		
		// plugin hooks
		$this->registerPluginHooks();
		
		// events
		$this->registerEvents();
	}
	
	/**
	 * Register plugin hook handlers
	 *
	 * @return void
	 */
	protected function registerPluginHooks() {
		
		// other hooks
		$hooks = $this->elgg()->hooks;
		
		// plugin hooks
		$hooks->registerHandler('register', 'menu:page', __NAMESPACE__ . '\Admin::pageMenu');
		$hooks->registerHandler('cron', 'minute', __NAMESPACE__ . '\Cron::minuteSync');
		$hooks->registerHandler('cron', 'daily', __NAMESPACE__ . '\Cron::dailyCleanup');
		
		// search hooks
		$hooks->registerHandler('search:fields', 'group', 'ColdTrick\ElasticSearch\SearchHooks::groupSearchFields');
		$hooks->registerHandler('search:fields', 'object', 'ColdTrick\ElasticSearch\SearchHooks::objectSearchFields');
		$hooks->registerHandler('search:fields', 'user', 'ColdTrick\ElasticSearch\SearchHooks::userSearchFields');
		$hooks->registerHandler('search:fields', 'entities', 'ColdTrick\ElasticSearch\SearchHooks::searchFields');
		
		$hooks->registerHandler('search:options', 'all', 'ColdTrick\ElasticSearch\SearchHooks::searchOptions');
		
		$hooks->registerHandler('search:results', 'entities', 'ColdTrick\ElasticSearch\SearchHooks::searchEntities');
		$hooks->registerHandler('search:results', 'combined:objects', 'ColdTrick\ElasticSearch\SearchHooks::searchEntities');
		$hooks->registerHandler('search:results', 'combined:all', 'ColdTrick\ElasticSearch\SearchHooks::searchEntities');
		
		// menu hooks
		$hooks->registerHandler('register', 'menu:search_list', __NAMESPACE__ . '\SearchHooks::registerSortMenu');
		$hooks->registerHandler('search_params', 'elasticsearch', __NAMESPACE__ . '\SearchHooks::filterProfileFields');
		$hooks->registerHandler('search_params', 'elasticsearch', __NAMESPACE__ . '\SearchHooks::sortByGroupMembersCount');
		$hooks->registerHandler('query_fields', 'elasticsearch', __NAMESPACE__ . '\SearchHooks::queryProfileFields');
		
		// extend exportable values
		$hooks->registerHandler('to:object', 'entity', __NAMESPACE__ . '\Export::entityToObject');
		$hooks->registerHandler('to:object', 'entity', __NAMESPACE__ . '\Export::entityRelationshipsToObject');
		$hooks->registerHandler('to:object', 'entity', __NAMESPACE__ . '\Export::entityMetadataToObject');
		$hooks->registerHandler('to:object', 'entity', __NAMESPACE__ . '\Export::entityCountersToObject');
		$hooks->registerHandler('to:object', 'entity', __NAMESPACE__ . '\Export::profileTagFieldsToTags');
		$hooks->registerHandler('to:object', 'entity', __NAMESPACE__ . '\Export::stripTags', 9999);
		$hooks->registerHandler('export:metadata_names', 'elasticsearch', __NAMESPACE__ . '\Export::exportProfileMetadata');
		$hooks->registerHandler('export:counters', 'elasticsearch', __NAMESPACE__ . '\Export::exportGroupMemberCount');
		$hooks->registerHandler('export:counters', 'elasticsearch', __NAMESPACE__ . '\Export::exportLikesCount');
		$hooks->registerHandler('export:counters', 'elasticsearch', __NAMESPACE__ . '\Export::exportCommentsCount');
		$hooks->registerHandler('index_entity_type_subtypes', 'elasticsearch', __NAMESPACE__ . '\Export::indexEntityTypeSubtypes');
		$hooks->registerHandler('to:entity', 'elasticsearch', __NAMESPACE__ . '\SearchHooks::sourceToEntity');
	}
	
	/**
	 * Register event handlers
	 *
	 * @return void
	 */
	protected function registerEvents() {
		$events = $this->elgg()->events;
		
		$events->registerHandler('create', 'all', __NAMESPACE__ . '\EventDispatcher::create');
		$events->registerHandler('update', 'all', __NAMESPACE__ . '\EventDispatcher::update');
		$events->registerHandler('delete', 'all', __NAMESPACE__ . '\EventDispatcher::delete');
		$events->registerHandler('disable', 'all', __NAMESPACE__ . '\EventDispatcher::disable');
		$events->registerHandler('ban', 'user', __NAMESPACE__ . '\EventDispatcher::banUser');
	}
}
