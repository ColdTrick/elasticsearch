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
	'elasticsearch:error:alias_not_configured' => "No alias is configured in the plugin settings",
	
	'elasticsearch:settings:host:header' => "ElasticSearch host settings",
	'elasticsearch:settings:host' => "API host",
	'elasticsearch:settings:host:description' => "You can configure multiple hosts by seperating them with a comma (,).",
	'elasticsearch:settings:index' => "Index to use for Elgg data",
	'elasticsearch:settings:index:suggestion' => "You need to configure an index to store all the Elgg data in. If you don't know which index to user, maybe '%s' is a suggestion?",
	'elasticsearch:settings:search_alias' => "Search index alias (optional)",
	'elasticsearch:settings:search_alias:description' => "If you wish to search in more then one index, you can configure an alias to seach in. This alias will also be applied to the Elgg index.",
	
	'elasticsearch:settings:features:header' => "Behaviour settings",
	'elasticsearch:settings:sync' => "Synchronize Elgg data to ElasticSearch",
	'elasticsearch:settings:sync:description' => "You need to enable synchronization to ElasticSearch, this will prevent inserting data on your ElasticSearch server if you're not ready yet.",
	'elasticsearch:settings:search' => "Use ElasticSearch as the search engine",
	'elasticsearch:settings:search:description' => "Once you've set up ElasticSearch correctly and it's populated with data, you can switch to use it as the search engine.",
	
	'elasticsearch:stats:cluster' => "Cluster information",
	'elasticsearch:stats:cluster_name' => "Cluster name",
	'elasticsearch:stats:es_version' => "ElasticSearch version",
	'elasticsearch:stats:lucene_version' => "Lucene version",
	
	'elasticsearch:stats:index:index' => "Index: %s",
	'elasticsearch:stats:index:stat' => "Statistic",
	'elasticsearch:stats:index:value' => "Value",
	
	'elasticsearch:stats:elgg' => "Elgg information",
	'elasticsearch:stats:elgg:total' => "Content that should have been indexed",
	'elasticsearch:stats:elgg:no_index_ts' => "New content to be indexed",
	'elasticsearch:stats:elgg:update' => "Updated content to be reindexed",
	'elasticsearch:stats:elgg:reindex' => "Content to be reindexed",
	'elasticsearch:stats:elgg:reindex:action' => "You can force a refresh of all already indexed entities by clicking on this action.",
	'elasticsearch:stats:elgg:reindex:last_ts' => "Current time to be used to compare if reindex is needed: %s",
	
	'elasticsearch:logging:description' => "Here you can find logging of the ElasticSearch API interface. Logfiles are seperated by year, month and day.",
	'elasticsearch:logging:root' => "Logging root",

	'elasticsearch:indices:index' => "Index",
	'elasticsearch:indices:alias' => "Alias",
	'elasticsearch:indices:create' => "Create",
	'elasticsearch:indices:optimize' => "Optimize",
	'elasticsearch:indices:flush' => "Flush",
	
	// menus
	'elasticsearch:menu:search_list:sort:title' => "Change the sort order of the results",
	'elasticsearch:menu:search_list:sort:relevance' => "Relevance",
	'elasticsearch:menu:search_list:sort:alpha_az' => "Alphabetical (A-Z)",
	'elasticsearch:menu:search_list:sort:alpha_za' => "Alphabetical (Z-A)",
	'elasticsearch:menu:search_list:sort:newest' => "Newest first",
	'elasticsearch:menu:search_list:sort:oldest' => "Oldest first",
	
	// forms
	'elasticsearch:forms:admin_search:query:placeholder' => "Enter your search query here",
	
	// actions
	'elasticsearch:action:admin:index_management:error:flush' => "An error occured during the flush of the index: %s",
	'elasticsearch:action:admin:index_management:error:optimize' => "An error occured during the optimization of the index: %s",
	'elasticsearch:action:admin:index_management:error:delete' => "An error occured during the deletion of the index: %s",
	'elasticsearch:action:admin:index_management:error:create:exists' => "You can't create the index '%s' it already exists",
	'elasticsearch:action:admin:index_management:error:create' => "An error occured during the creation of the index: %s",
	'elasticsearch:action:admin:index_management:error:task' => "The task '%s' is not supported",
	'elasticsearch:action:admin:index_management:error:add_alias:exists' => "The alias '%s' already exists on the index '%s'",
	'elasticsearch:action:admin:index_management:error:add_alias' => "An error occured while adding the alias '%s' to the index '%s'",
	'elasticsearch:action:admin:index_management:error:delete_alias:exists' => "The alias '%s' doesn't exists on the index '%s'",
	'elasticsearch:action:admin:index_management:error:delete_alias' => "An error occured while deleting the alias '%s' from the index '%s'",
	
	'elasticsearch:action:admin:index_management:flush' => "The index '%s' was flushed",
	'elasticsearch:action:admin:index_management:optimize' => "The index '%s' was optimized",
	'elasticsearch:action:admin:index_management:delete' => "The index '%s' was deleted",
	'elasticsearch:action:admin:index_management:create' => "The index '%s' was created",
	'elasticsearch:action:admin:index_management:add_alias' => "The alias '%s' was added to the index '%s'",
	'elasticsearch:action:admin:index_management:delete_alias' => "The alias '%s' was deleted from the index '%s'",
	'' => "",
);
