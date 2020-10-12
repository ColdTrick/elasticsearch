<?php

return array(
	'admin:elasticsearch' => "ElasticSearch",
	'admin:elasticsearch:statistics' => "Statistics",
	'admin:elasticsearch:search' => "Search",
	'admin:elasticsearch:indices' => "Indices",
	'admin:elasticsearch:inspect' => "Inspect",
	
	'elasticsearch:upgrade:2019120300:title' => "Remove Elasticsearch logs",
	'elasticsearch:upgrade:2019120300:description' => "Removes all the old style log files. As logging is handled differently",
	
	'elasticsearch:menu:entity:inspect' => "Inspect in Elasticsearch",
	
	'elasticsearch:index_management:exception:config:index' => "The hook 'config:index', 'elasticsearch' should return an array for the index configuration",
	'elasticsearch:index_management:exception:config:mapping' => "The hook 'config:mapping', 'elasticsearch' should return an array for the mapping configuration",
	
	'elasticsearch:admin_search:results' => "Search Results",
	'elasticsearch:admin_search:results:info' => "Results will be shown here",
	
	'elasticsearch:error:no_client' => "Unable to create an ElasticSearch client",
	'elasticsearch:error:host_unavailable' => "ElasticSearch API host unavailable",
	'elasticsearch:error:no_index' => "No index provided for the given action",
	'elasticsearch:error:index_not_exists' => "The given index doesn't exist: %s",
	'elasticsearch:error:alias_not_configured' => "No alias is configured in the plugin settings",
	'elasticsearch:error:search' => "An error occured during your search operation. Please contact the site administrator if this problem persists.",
	
	'elasticsearch:settings:pattern:float' => "Only numbers (0-9) and period (.) are allowed",
	
	'elasticsearch:settings:host:header' => "ElasticSearch host settings",
	'elasticsearch:settings:host' => "API host",
	'elasticsearch:settings:host:description' => "You can configure multiple hosts by seperating them with a comma (,).",
	'elasticsearch:settings:ignore_ssl' => "Disable SSL verification",
	'elasticsearch:settings:ignore_ssl:description' => "If your hosts use HTTPS, but you use self-signed certificates you can disable SSL verification with this setting.",
	'elasticsearch:settings:index' => "Index to use for Elgg data",
	'elasticsearch:settings:index:suggestion' => "You need to configure an index to store all the Elgg data in. If you don't know which index to use, maybe '%s' is a suggestion?",
	'elasticsearch:settings:search_alias' => "Search index alias (optional)",
	'elasticsearch:settings:search_alias:description' => "If you wish to search in more then one index, you can configure an alias to search in. This alias will also be applied to the Elgg index.",
	
	'elasticsearch:settings:features:header' => "Behaviour settings",
	'elasticsearch:settings:sync' => "Synchronize Elgg data to ElasticSearch",
	'elasticsearch:settings:sync:description' => "You need to enable synchronization to ElasticSearch, this will prevent inserting data on your ElasticSearch server if you're not ready yet.",
	'elasticsearch:settings:search' => "Use ElasticSearch as the search engine",
	'elasticsearch:settings:search:description' => "Once you've set up ElasticSearch correctly and it's populated with data, you can switch to use it as the search engine.",
	'elasticsearch:settings:search_score' => "Show search score in results",
	'elasticsearch:settings:search_score:description' => "Display the search result score to administrators in the search results. This can help explain why results are ordered as they are.",
	'elasticsearch:settings:cron_validate' => "Validate the search index daily",
	'elasticsearch:settings:cron_validate:description' => "Validate the index to make sure no content is left in the index that shouldn't be there and all content that should be there is present.",
	
	'elasticsearch:settings:type_boosting:title' => "Content Type Boosting",
	'elasticsearch:settings:type_boosting:info' => "If you want the score of a content type to be boosted during query time you can configure multipliers here.
	If you want similar search results to be ordered based on the type you should use small multipliers like 1.01.
	If you always want users to be on top of a combined query, regardless of the quality of the hit, you can use big multipliers.
	
	More information on query time boosting can be found in the elasticsearch documentation website.",
	'elasticsearch:settings:type_boosting:type' => "Content Type",
	'elasticsearch:settings:type_boosting:multiplier' => "Multiplier",
	
	'elasticsearch:settings:decay:title' => "Content Decay",
	'elasticsearch:settings:decay:info' => "If configured the decay score multiplier will be applied to the content results.",
	'elasticsearch:settings:decay_offset' => "Offset",
	'elasticsearch:settings:decay_offset:help' => "Enter the number of days before (min) the decay multiplier will be applied.",
	'elasticsearch:settings:decay_scale' => "Scale",
	'elasticsearch:settings:decay_scale:help' => "Enter the number of days until (max) the lowest decay multiplier will be applied.",
	'elasticsearch:settings:decay_decay' => "Decay",
	'elasticsearch:settings:decay_decay:help' => "Enter the decay multiplier that will be applied when scale is reached. Enter a number between 1 and 0. The lower the number, the lower the score.",
	'elasticsearch:settings:decay_time_field' => "Time field",
	'elasticsearch:settings:decay_time_field:help' => "Select the time field to apply the decay on",
	'elasticsearch:settings:decay_time_field:time_created' => "Creation date",
	'elasticsearch:settings:decay_time_field:time_updated' => "Last update",
	'elasticsearch:settings:decay_time_field:last_action' => "Last action",
	
	'elasticsearch:stats:cluster' => "Cluster information",
	'elasticsearch:stats:cluster_name' => "Cluster name",
	'elasticsearch:stats:es_version' => "ElasticSearch version",
	'elasticsearch:stats:lucene_version' => "Lucene version",
	
	'elasticsearch:stats:index:index' => "Index: %s",
	'elasticsearch:stats:index:stat' => "Statistic",
	'elasticsearch:stats:index:value' => "Value",
	
	'elasticsearch:stats:elgg' => "Elgg information",
	'elasticsearch:stats:elgg:total' => "Content that should have been indexed",
	'elasticsearch:stats:elgg:total:help' => "This could include content (like banned users) which isn't actually indexed by Elasticsearch.",
	'elasticsearch:stats:elgg:no_index_ts' => "New content to be indexed",
	'elasticsearch:stats:elgg:update' => "Updated content to be reindexed",
	'elasticsearch:stats:elgg:reindex' => "Content to be reindexed",
	'elasticsearch:stats:elgg:reindex:action' => "You can force a refresh of all already indexed entities by clicking on this action.",
	'elasticsearch:stats:elgg:reindex:last_ts' => "Current time to be used to compare if reindex is needed: %s",
	'elasticsearch:stats:elgg:delete' => "Content waiting to be deleted",
	
	'elasticsearch:indices:index' => "Index",
	'elasticsearch:indices:alias' => "Alias",
	'elasticsearch:indices:aliases' => "aliases",
	'elasticsearch:indices:create' => "Create",
	'elasticsearch:indices:mappings' => "Mappings",
	'elasticsearch:indices:mappings:add' => "Add / update",
	
	'elasticsearch:inspect:guid' => "Please enter the GUID of the entity you wish to inspect",
	'elasticsearch:inspect:guid:help' => "All entities in Elgg have a GUID, mostly you can find this in the URL to the entity (eg blog/view/1234)",
	'elasticsearch:inspect:submit' => "Inspect",
	
	'elasticsearch:inspect:result:title' => "Inspection results",
	'elasticsearch:inspect:result:elgg' => "Elgg",
	'elasticsearch:inspect:result:elasticsearch' => "Elasticsearch",
	'elasticsearch:inspect:result:error:type_subtype' => "The type/subtype of this entity isn't supported for indexing in Elasticsearch.",
	'elasticsearch:inspect:result:error:not_indexed' => "The entity is not yet indexed",
	'elasticsearch:inspect:result:last_indexed:none' => "This entity has not yet been indexed",
	'elasticsearch:inspect:result:last_indexed:scheduled' => "This entity is scheduled to be (re)indexed",
	'elasticsearch:inspect:result:last_indexed:time' => "This entity was last indexed: %s",
	'elasticsearch:inspect:result:reindex' => "Schedule for reindexing",
	'elasticsearch:inspect:result:delete' => "Remove entity from index",
	
	// menus
	'elasticsearch:menu:search_list:sort:title' => "Change the sort order of the results",
	'elasticsearch:menu:search_list:sort:relevance' => "Relevance",
	'elasticsearch:menu:search_list:sort:alpha_az' => "Alphabetical (A-Z)",
	'elasticsearch:menu:search_list:sort:alpha_za' => "Alphabetical (Z-A)",
	'elasticsearch:menu:search_list:sort:newest' => "Newest first",
	'elasticsearch:menu:search_list:sort:oldest' => "Oldest first",
	'elasticsearch:menu:search_list:sort:member_count' => "Member count",
	
	// forms
	'elasticsearch:forms:admin_search:query:placeholder' => "Enter your search query here",
	
	// CLI
	'elasticsearch:cli:error:client' => "The Elasticsearch client isn't ready yet, please check the plugin settings",
	
	'elasticsearch:progress:start:no_index_ts' => "Adding new documents to index",
	'elasticsearch:progress:start:update' => "Updating documents in index",
	'elasticsearch:progress:start:reindex' => "Reindexing documents in index",
	
	// Sync
	'elasticsearch:cli:sync:description' => "Synchonize the Elgg database to the Elasticsearch index",
	'elasticsearch:cli:sync:delete' => "Old documents have been removed from the index",
	'elasticsearch:cli:sync:delete:error' => "An error occured while removing old documents from the index",
	'elasticsearch:cli:sync:no_index_ts' => "Added new documents to the index",
	'elasticsearch:cli:sync:no_index_ts:error' => "An error occured while adding new documents to the index",
	'elasticsearch:cli:sync:update' => "Updated documents in the index",
	'elasticsearch:cli:sync:update:error' => "An error occured while updating documents in the index",
	'elasticsearch:cli:sync:reindex' => "Reindexed documents in the index",
	'elasticsearch:cli:sync:reindex:error' => "An error occured while reindexing documents in the index",
	
	// actions
	'elasticsearch:action:admin:index_management:error:delete' => "An error occured during the deletion of the index: %s",
	'elasticsearch:action:admin:index_management:error:create:exists' => "You can't create the index '%s' it already exists",
	'elasticsearch:action:admin:index_management:error:create' => "An error occured during the creation of the index: %s",
	'elasticsearch:action:admin:index_management:error:add_mappings' => "An error occured during the creation of the mappings for the index: %s",
	'elasticsearch:action:admin:index_management:error:task' => "The task '%s' is not supported",
	'elasticsearch:action:admin:index_management:error:add_alias:exists' => "The alias '%s' already exists on the index '%s'",
	'elasticsearch:action:admin:index_management:error:add_alias' => "An error occured while adding the alias '%s' to the index '%s'",
	'elasticsearch:action:admin:index_management:error:delete_alias:exists' => "The alias '%s' doesn't exists on the index '%s'",
	'elasticsearch:action:admin:index_management:error:delete_alias' => "An error occured while deleting the alias '%s' from the index '%s'",
	
	'elasticsearch:action:admin:index_management:delete' => "The index '%s' was deleted",
	'elasticsearch:action:admin:index_management:create' => "The index '%s' was created",
	'elasticsearch:action:admin:index_management:add_mappings' => "Mappings for the index '%s' are created",
	'elasticsearch:action:admin:index_management:add_alias' => "The alias '%s' was added to the index '%s'",
	'elasticsearch:action:admin:index_management:delete_alias' => "The alias '%s' was deleted from the index '%s'",
	
	'elasticsearch:action:admin:reindex_entity:success' => "The entity is scheduled for reindexing",
	'elasticsearch:action:admin:delete_entity:success' => "The entity is scheduled for deletion from the index",
	
	'elasticsearch:search_score' => "Score: %s",
);
