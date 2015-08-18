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
	if (elasticsearch_get_setting('search') === 'yes') {
		// unregister some default search hooks
		elgg_unregister_plugin_hook_handler('search', 'object', 'search_objects_hook');
		elgg_unregister_plugin_hook_handler('search', 'user', 'search_users_hook');
		elgg_unregister_plugin_hook_handler('search', 'group', 'search_groups_hook');
		
		// no need for special tags search
		elgg_unregister_plugin_hook_handler('search_types', 'get_types', 'search_custom_types_tags_hook');
		elgg_unregister_plugin_hook_handler('search', 'tags', 'search_tags_hook');
		
		// register own search hooks
		elgg_register_plugin_hook_handler('search', 'group', array('ColdTrick\ElasticSearch\Search', 'searchGroups'));
		elgg_register_plugin_hook_handler('search', 'user', array('ColdTrick\ElasticSearch\Search', 'searchUsers'));
		elgg_register_plugin_hook_handler('search', 'object', array('ColdTrick\ElasticSearch\Search', 'searchObjects'));
	}
	
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
