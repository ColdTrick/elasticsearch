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
	
	// ajax views
	elgg_register_ajax_view('elasticsearch/logging/view');
	
	// plugin hooks
	elgg_register_plugin_hook_handler('register', 'menu:page', array('ColdTrick\ElasticSearch\Admin', 'pageMenu'));
	elgg_register_plugin_hook_handler('cron', 'minute', array('ColdTrick\ElasticSearch\Cron', 'minuteSync'));
	
	// events
	elgg_register_event_handler('create', 'all', array('ColdTrick\ElasticSearch\EventDispatcher', 'create'));
	elgg_register_event_handler('update', 'all', array('ColdTrick\ElasticSearch\EventDispatcher', 'update'));
	elgg_register_event_handler('delete', 'all', array('ColdTrick\ElasticSearch\EventDispatcher', 'delete'));
	
	// actions
	elgg_register_action('elasticsearch/admin_search', dirname(__FILE__) . '/actions/admin_search.php', 'admin');
	
	elgg_register_action('elasticsearch/admin/index_management', dirname(__FILE__) . '/actions/admin/index_management.php', 'admin');
	elgg_register_action('elasticsearch/admin/reindex', dirname(__FILE__) . '/actions/admin/reindex.php', 'admin');
}
