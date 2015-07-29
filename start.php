<?php
/**
 * Main file for the Elasticsearch plugin
 */

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
	
	// plugin hooks
	elgg_register_plugin_hook_handler('register', 'menu:page', array('ColdTrick\ElasticSearch\Admin', 'pageMenu'));
}
