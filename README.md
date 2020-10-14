# Elasticsearch

![Elgg 3.3](https://img.shields.io/badge/Elgg-3.3-green.svg)
![ElasticSearch 7.4](https://img.shields.io/badge/ElasticSearch-7.4-green.svg)
[![Build Status](https://scrutinizer-ci.com/g/ColdTrick/elasticsearch/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ColdTrick/elasticsearch/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ColdTrick/elasticsearch/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ColdTrick/elasticsearch/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/coldtrick/elasticsearch/v/stable.svg)](https://packagist.org/packages/coldtrick/elasticsearch)
[![License](https://poser.pugx.org/coldtrick/elasticsearch/license.svg)](https://packagist.org/packages/coldtrick/elasticsearch)

An Elasticsearch implementation for Elgg

## Requirements

A working [ElasticSearch](https://www.elastic.co/) server is required. Also the minute cron has to be working on your Elgg installation. 
The minute cron is used to update the index with all the required changes (create/update/delete).

The current supported version of ElasticSearch is: 7.4.x

## Configuration

### Settings

The plugin settings allow you to configure the following:

 - Hosts: 1 or more hosts need to be configured (full URL + optional portnumber). You can provide more hosts comma seperated.
 - Index: Name of the index used for indexing Elgg data and search queries
 - Search alias (optional): Name of the alias to use in search queries, this allows for easy searching across multiple indices

### Index Management

The index management page (found under Administer -> ElasticSearch -> Indices in the admin sidebar) allows you to perform various actions on 
all the indexes available on the ElastisSearch server. The following actions are supported:

- Create: This action can only be performed if the index configured in the plugin settings page is not yet available. 
It will create the default index configuration to be used for search.
- Alias: Add/remove the configured alias to the index (this allows searching across multiple indices)
- Delete: This will remove the index from the server (this action can not be undone)
 
## Administration

### Log files

Based on the log level of your Elgg site, there will also be logging of the ElasticSearch PHP client library. 
Logging will appear in the same location as all other Elgg logs.

### Statistics

You can find various statistics on the Administer -> ElasticSearch -> Statistics page. Elgg statistics report on the amount of 
entities found in the Elgg database that should be in the index. It also reports on the amount of entities that need to be 
added/updated/deleted in the index and that are currently waiting on the minute cron to process them.

Also some statistics from the Elastic Cluster are shown like the status and the version information.

You can also find statistics for all available indexes on this page.

### CLI commands

CLI commands are available to be used with the default `elgg-cli` command

#### elasticsearch:sync

This command will synchronize all pending entities to the Elasticsearch index. This is especialy usefull during the reindexing 
process of the database because a lot of entities need to be indexed. Using the normal cron task in this case could take a long time.

## Recommendations

Use the [Search Advanced](http://github.com/ColdTrick/search_advanced) plugin to add extra features to search. If both are enabled this 
plugin provides a menu to sort/order the results.

## Developers

### Plugin hooks

#### 'boostable_types', 'elasticsearch'

Return an array of type.subtype to be used for configuaring boosting in Elasticsearch.

In the format:
```php
[
	'type.subtype',
]
```

Defaults to the registered searchable type/subtypes for Elasticsearch.

#### 'config:index', 'elasticsearch'

Return an array with the index configuration to be used by Elasticsearch.

#### 'config:mapping', 'elasticsearch'

Return an array with the mapping configuration to be used by Elasticsearch.

#### 'export:counters', 'elasticsearch'

Return an array of counters to be exported to Elasticsearch. 

In the format:
```php
[
	'counter_name' => counter_value
]
```

Params contain:
- `entity`: the `ElggEntity` being exported

#### 'export:metadata_names', 'elasticsearch'

Return an array of metadata names to be exported to Elasticsearch.

Params contain:
- `entity`: the `ElggEntity` being exported

#### 'index_entity_type_subtypes', 'elasticsearch'

Return an array of type/subtypes allowed to be indexed by Elasticsearch.

In the format:
```php
[
	'type' => ['subtype1', 'subtype2'],
]
```

Defaults to all registered searchable type/subtypes in Elgg.

#### 'index:entity:prevent', 'elasticsearch'

Return `true` if the provided entity shouldn't be added to the Elasticsearch index

Params contain:
- `entity`: the `ElggEntity` about to be indexed

Default: `false`

#### 'index:entity:type', 'elasticsearch'

Return a string under which type/subtype the entity should be indexed. This is used for type filtering during the search (eg. all blogs)

Params contain:
- `entity`: the `ElggEntity` about to be indexed
- `default`: the default type/subtype for this entity

Default: `{entity_type}.{entity_subtype}` (eg. `object.blog`)

#### 'params', 'elasticsearch'

Return an array of parameters to be using in initializing the `\ColdTrick\ElasticSearch\Client` Elasticsearch client.

#### 'search', 'type_subtype_pairs'

Return an array of type/subtypes allowed to be searched by Elasticsearch.

In the format:
```php
[
	'type' => ['subtype1', 'subtype2'],
]
```

Defaults to all registered searchable type/subtypes in Elgg.

#### 'search_params', 'elasticsearch'

Return an `\ColdTrick\ElasticSearch\Di\SearchService` to be used for the search. This allows you to alter the search parameters 
in Elasticsearch.

Params contain:
- `search_params`: an array of the search parameters as provided by Elgg search

#### 'to:entity:before', 'elasticsearch'

Change the Elasticsearch hit data before it's converted to an `ElggEntity`

Params contain:
- `hit`: the result from Elasticsearch
- `search_params`: an array of the search parameters as provided by Elgg search

#### 'to:entity', 'elasticsearch'

Return an `ElggEntity` based on the search result data from Elasticsearch.

Params contain:
- `hit`: the result from Elasticsearch
- `search_params`: an array of the search parameters as provided by Elgg search

### Parameters passed to `elgg_search`

#### field_boosting

In the parameters passed to `elgg_search` you can add a configuration to control field boosting in Elasticsearch. Add the key
`field_boosting` to the array which holds an array with fieldname and the boosting for that field.

Example:
```php
[
	'field_boosting' => [
		'title' => 2, // title is worth double
		'description' => 0.5 // description is worth half
	],
]
```
