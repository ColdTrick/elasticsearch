<?php

$plugin = elgg_extract('entity', $vars);

$noyes_options = array(
	'no' => elgg_echo('option:no'),
	'yes' => elgg_echo('option:yes'),
);

// host configuration
$host = elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('elasticsearch:settings:host'),
	'#help' => elgg_echo('elasticsearch:settings:host:description'),
	'name' => 'params[host]',
	'value' => $plugin->host,
]);

$host .= elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('elasticsearch:settings:index'),
	'#help' => elgg_echo('elasticsearch:settings:index:suggestion', [elgg_get_config('dbname')]),
	'name' => 'params[index]',
	'value' => $plugin->index,
]);

$host .= elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('elasticsearch:settings:search_alias'),
	'#help' => elgg_echo('elasticsearch:settings:search_alias:description'),
	'name' => 'params[search_alias]',
	'value' => $plugin->search_alias,
]);

echo elgg_view_module('inline', elgg_echo('elasticsearch:settings:host:header'), $host);

// features
$features = elgg_view_field([
	'#type' => 'select',
	'#label' => elgg_echo('elasticsearch:settings:sync'),
	'#help' => elgg_echo('elasticsearch:settings:sync:description'),
	'name' => 'params[sync]',
	'value' => $plugin->sync,
	'options_values' => $noyes_options,
]);

$features .= elgg_view_field([
	'#type' => 'select',
	'#label' => elgg_echo('elasticsearch:settings:search'),
	'#help' => elgg_echo('elasticsearch:settings:search:description'),
	'name' => 'params[search]',
	'value' => $plugin->search,
	'options_values' => $noyes_options,
]);

echo elgg_view_module('inline', elgg_echo('elasticsearch:settings:features:header'), $features);
