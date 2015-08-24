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
	elgg_register_css('elgg.icons', elgg_get_simplecache_url('css', 'elements/icons'));
	
	// ajax views
	elgg_register_ajax_view('elasticsearch/logging/view');
	
	// plugin hooks
	elgg_register_plugin_hook_handler('register', 'menu:page', array('ColdTrick\ElasticSearch\Admin', 'pageMenu'));
	elgg_register_plugin_hook_handler('cron', 'minute', array('ColdTrick\ElasticSearch\Cron', 'minuteSync'));

	// search hooks
	elastic_prepare_search_hooks();
	
	// menu hooks
	elgg_register_plugin_hook_handler('register', 'menu:search_list', array('ColdTrick\ElasticSearch\Search', 'registerSortMenu'));
	
	// extend exportable values
	elgg_register_plugin_hook_handler('to:object', 'entity', array('ColdTrick\ElasticSearch\Client', 'entityToObject'));
	
	// events
	elgg_register_event_handler('create', 'all', array('ColdTrick\ElasticSearch\EventDispatcher', 'create'));
	elgg_register_event_handler('update', 'all', array('ColdTrick\ElasticSearch\EventDispatcher', 'update'));
	elgg_register_event_handler('delete', 'all', array('ColdTrick\ElasticSearch\EventDispatcher', 'delete'));
	elgg_register_event_handler('disable', 'all', array('ColdTrick\ElasticSearch\EventDispatcher', 'disable'));
	
	// actions
	elgg_register_action('elasticsearch/admin_search', dirname(__FILE__) . '/actions/admin_search.php', 'admin');
	
	elgg_register_action('elasticsearch/admin/index_management', dirname(__FILE__) . '/actions/admin/index_management.php', 'admin');
	elgg_register_action('elasticsearch/admin/reindex', dirname(__FILE__) . '/actions/admin/reindex.php', 'admin');
}
