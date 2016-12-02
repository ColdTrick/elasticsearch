<?php
/**
 * Main file for the Elasticsearch plugin
 */

define('ELASTICSEARCH_INDEXED_NAME', 'elasticsearch_last_indexed');

@include_once(dirname(__FILE__) . '/vendor/autoload.php');

require_once(dirname(__FILE__) . '/lib/functions.php');

// register default Elgg events
elgg_register_event_handler("init", "system", "elasticsearch_init");

/**
 * Gets called when the Elgg system initializes
 *
 * @return void
 */
function elasticsearch_init() {
	
	// css
	elgg_extend_view('css/elgg', 'css/elasticsearch/site.css');
	elgg_register_css('elgg.icons', elgg_get_simplecache_url('css', 'elements/icons'));
	
	// ajax views
	elgg_register_ajax_view('elasticsearch/logging/view');
	
	// view extends
	elgg_extend_view('forms/search_advanced/search', 'elasticsearch/search/suggest', 800);
	
	// plugin hooks
	elgg_register_plugin_hook_handler('register', 'menu:page', array('ColdTrick\ElasticSearch\Admin', 'pageMenu'));
	elgg_register_plugin_hook_handler('cron', 'minute', '\ColdTrick\ElasticSearch\Cron::minuteSync');
	elgg_register_plugin_hook_handler('cron', 'daily', '\ColdTrick\ElasticSearch\Cron::dailyCleanup');

	// search hooks
	elastic_prepare_search_hooks();
	
	// menu hooks
	elgg_register_plugin_hook_handler('register', 'menu:search_list', array('ColdTrick\ElasticSearch\SearchHooks', 'registerSortMenu'));
	elgg_register_plugin_hook_handler('search_params', 'elasticsearch', array('ColdTrick\ElasticSearch\SearchHooks', 'filterProfileFields'));
	elgg_register_plugin_hook_handler('search_params', 'elasticsearch', array('ColdTrick\ElasticSearch\SearchHooks', 'sortByGroupMembersCount'));
	elgg_register_plugin_hook_handler('query_fields', 'elasticsearch', array('ColdTrick\ElasticSearch\SearchHooks', 'queryProfileFields'));
	
	// extend exportable values
	elgg_register_plugin_hook_handler('to:object', 'entity', array('ColdTrick\ElasticSearch\Export', 'entityToObject'));
	elgg_register_plugin_hook_handler('to:object', 'entity', array('ColdTrick\ElasticSearch\Export', 'entityRelationshipsToObject'));
	elgg_register_plugin_hook_handler('to:object', 'entity', array('ColdTrick\ElasticSearch\Export', 'entityMetadataToObject'));
	elgg_register_plugin_hook_handler('to:object', 'entity', array('ColdTrick\ElasticSearch\Export', 'entityCountersToObject'));
	elgg_register_plugin_hook_handler('to:object', 'entity', array('ColdTrick\ElasticSearch\Export', 'profileTagFieldsToTags'));
	elgg_register_plugin_hook_handler('to:object', 'entity', array('ColdTrick\ElasticSearch\Export', 'profileFieldsToProfileObject'));
	elgg_register_plugin_hook_handler('to:object', 'entity', array('ColdTrick\ElasticSearch\Export', 'stripTags'), 9999);
	elgg_register_plugin_hook_handler('export:metadata_names', 'elasticsearch', array('ColdTrick\ElasticSearch\Export', 'exportProfileMetadata'));
	elgg_register_plugin_hook_handler('export:counters', 'elasticsearch', array('ColdTrick\ElasticSearch\Export', 'exportGroupMemberCount'));
	elgg_register_plugin_hook_handler('index_entity_type_subtypes', 'elasticsearch', array('ColdTrick\ElasticSearch\Export', 'indexEntityTypeSubtypes'));
	elgg_register_plugin_hook_handler('to:entity', 'elasticsearch', array('ColdTrick\ElasticSearch\SearchHooks', 'sourceToEntity'));
	
	// events
	elgg_register_event_handler('create', 'all', array('ColdTrick\ElasticSearch\EventDispatcher', 'create'));
	elgg_register_event_handler('update', 'all', array('ColdTrick\ElasticSearch\EventDispatcher', 'update'));
	elgg_register_event_handler('delete', 'all', array('ColdTrick\ElasticSearch\EventDispatcher', 'delete'));
	elgg_register_event_handler('disable', 'all', array('ColdTrick\ElasticSearch\EventDispatcher', 'disable'));
	elgg_register_event_handler('ban', 'user', array('ColdTrick\ElasticSearch\EventDispatcher', 'banUser'));
	
	// actions
	elgg_register_action('elasticsearch/admin_search', dirname(__FILE__) . '/actions/admin_search.php', 'admin');
	
	elgg_register_action('elasticsearch/admin/index_management', dirname(__FILE__) . '/actions/admin/index_management.php', 'admin');
	elgg_register_action('elasticsearch/admin/reindex', dirname(__FILE__) . '/actions/admin/reindex.php', 'admin');
	elgg_register_action('elasticsearch/admin/download_log', dirname(__FILE__) . '/actions/admin/download_log.php', 'admin');
}
