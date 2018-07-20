# Elasticsearch

![Elgg 3.0](https://img.shields.io/badge/Elgg-3.0-green.svg)
[![Build Status](https://scrutinizer-ci.com/g/ColdTrick/elasticsearch/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ColdTrick/elasticsearch/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ColdTrick/elasticsearch/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ColdTrick/elasticsearch/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/coldtrick/elasticsearch/v/stable.svg)](https://packagist.org/packages/coldtrick/elasticsearch)
[![License](https://poser.pugx.org/coldtrick/elasticsearch/license.svg)](https://packagist.org/packages/coldtrick/elasticsearch)

An Elasticsearch implementation for Elgg

## Requirements

A working [ElasticSearch](https://www.elastic.co/) server is required. Also the minute cron has to be working on your Elgg installation. The minute cron is used to update the index with all the required changes (create/update/delete).

## Configuration

### Settings

The plugin settings allow you to configure the following:

 - Hosts: 1 or more hosts need to be configured (full URL + optional portnumber). You can provide more hosts comma seperated.
 - Index: Name of the index used for indexing Elgg data and search queries
 - Search alias (optional): Name of the alias to use in search queries, this allows for easy searching across multiple indices

### Index Management

The index management page (found under Administer -> ElasticSearch -> Indices in the admin sidebar) allows you to perform various actions on all the indexes available on the ElastisSearch server. The following actions are supported:

- Create: This action can only be performed if the index configured in the plugin settings page is not yet available. It will create the default index configuration to be used for search.
- Alias: Add/remove the configured alias to the index (this allows searching across multiple indices)
- Delete: This will remove the index from the server (this action can not be undone)
- Optimize: This performs the [optimize](https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-optimize.html) operation on the index
- Flush: The performs the [flush](https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-flush.html) operation on the index
 
## Administration

### Log files

Based on the log level of your Elgg site, there will also be logging of the Elastic Search PHP client library. Log messages will be written to a file. For every day there will be a file. You can view them on the Administer -> ElasticSearch -> Logging page.

### Statistics

You can find various statistics on the Administer -> ElasticSearch -> Statistics page. Elgg statistics report on the amount of entities found in the Elgg database that should be in the index. It also reports on the amount of entities that need to be added/updated/deleted in the index and that are currently waiting on the minute cron to process them.

Also some statistics from the Elastic Cluster are shown like the status and the version information.

You can also find statistics for all available indexes on this page.

## Recommendations

Use the [Search Advanced](http://github.com/ColdTrick/search_advanced) plugin to add extra features to search. If both are enabled this plugin provides a menu to sort/order the results.
