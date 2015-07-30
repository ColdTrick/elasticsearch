<?php

return array(
	'admin:elasticsearch' => "ElasticSearch",
	'admin:elasticsearch:statistics' => "Statistics",
	'admin:elasticsearch:search' => "Search",
	'admin:elasticsearch:logging' => "Logging",
	'admin:elasticsearch:indices' => "Indices",
	
	'elasticsearch:admin_search:results' => "Search Results",
	'elasticsearch:admin_search:results:info' => "Results will be shown here",
	
	'elasticsearch:error:no_client' => "Unable to create an ElasticSearch client",
	'elasticsearch:error:host_unavailable' => "ElasticSearch API host unavailable",
	'elasticsearch:error:no_index' => "No index provided for the given action",
	'elasticsearch:error:index_not_exists' => "The given index doesn't exist: %s",
	
	'elasticsearch:settings:host' => "ElasticSearch API host",
	'elasticsearch:settings:host:description' => "You can configure multiple hosts by seperating them with a comma (,).",
	'elasticsearch:settings:index' => "Index to use for Elgg data",
	'elasticsearch:settings:index:suggestion' => "You need to configure an index to store all the Elgg data in. If you don't know which index to user, maybe '%s' is a suggestion?",
	'elasticsearch:settings:sync' => "Synchronize Elgg data to ElasticSearch",
	'elasticsearch:settings:sync:description' => "You need to enable synchronization to ElasticSearch, this will prevent inserting data on your ElasticSearch server if you're not ready yet.",
	
	'elasticsearch:stats:cluster' => "Cluster information",
	'elasticsearch:stats:cluster_name' => "Cluster name",
	'elasticsearch:stats:es_version' => "ElasticSearch version",
	'elasticsearch:stats:lucene_version' => "Lucene version",
	
	'elasticsearch:stats:index:index' => "Index: %s",
	'elasticsearch:stats:index:stat' => "Statistic",
	'elasticsearch:stats:index:value' => "Value",
	
	'elasticsearch:stats:elgg' => "Elgg information",
	'elasticsearch:stats:elgg:total' => "Content that should be indexed",
	'elasticsearch:stats:elgg:no_index_ts' => "New content to be indexed",
	'elasticsearch:stats:elgg:update' => "Updated content to be reindexed",
	'elasticsearch:stats:elgg:reindex' => "Content to be reindexed",
	
	'elasticsearch:logging:description' => "Here you can find logging of the ElasticSearch API interface. Logfiles are seperated by year, month and day.",
	'elasticsearch:logging:root' => "Logging root",

	'elasticsearch:indices:index' => "Index",
	'elasticsearch:indices:create' => "Create",
	'elasticsearch:indices:optimize' => "Optimize",
	'elasticsearch:indices:flush' => "Flush",
	
	// forms
	'elasticsearch:forms:admin_search:query:placeholder' => "Enter your search query here",
	
	// actions
	'elasticsearch:action:admin:index_management:error:flush' => "An error occured during the flush of the index: %s",
	'elasticsearch:action:admin:index_management:error:optimize' => "An error occured during the optimization of the index: %s",
	'elasticsearch:action:admin:index_management:error:delete' => "An error occured during the deletion of the index: %s",
	'elasticsearch:action:admin:index_management:error:create:exists' => "You can't create the index '%s' it already exists",
	'elasticsearch:action:admin:index_management:error:create' => "An error occured during the creation of the index: %s",
	'elasticsearch:action:admin:index_management:error:task' => "The task '%s' is not supported",
	'elasticsearch:action:admin:index_management:flush' => "The index '%s' was flushed",
	'elasticsearch:action:admin:index_management:optimize' => "The index '%s' was optimized",
	'elasticsearch:action:admin:index_management:delete' => "The index '%s' was deleted",
	'elasticsearch:action:admin:index_management:create' => "The index '%s' was created",
	'' => "",
);
