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

$features .= elgg_view_field([
	'#type' => 'select',
	'#label' => elgg_echo('elasticsearch:settings:cron_validate'),
	'#help' => elgg_echo('elasticsearch:settings:cron_validate:description'),
	'name' => 'params[cron_validate]',
	'value' => $plugin->cron_validate,
	'options_values' => $noyes_options,
]);

echo elgg_view_module('inline', elgg_echo('elasticsearch:settings:features:header'), $features);

// boosting of types
$type_subtypes = elasticsearch_get_registered_entity_types_for_search();
$types = \ColdTrick\ElasticSearch\SearchHooks::entityTypeSubtypesToSearchTypes($type_subtypes);
if (!empty($types)) {
	
	$boosting = elgg_view('output/longtext', [
		'value' => elgg_echo('elasticsearch:settings:type_boosting:info'),
	]);
	
	$rows = '<tr><th>' . elgg_echo('elasticsearch:settings:type_boosting:type') . '</th><th>' . elgg_echo('elasticsearch:settings:type_boosting:multiplier') . '</th></tr>';
	foreach ($types as $type) {
		$boost_input = elgg_view_field([
			'#type' => 'text',
			'#class' => 'man',
			'name' => "params[type_boosting_$type]",
			'value' => $plugin->{"type_boosting_$type"},
		]);
		
		$rows .= "<tr><td>{$type}</td><td>{$boost_input}</td></tr>";
			
	}
	$boosting .= elgg_format_element('table', ['class' => 'elgg-table'], $rows);

	echo elgg_view_module('inline', elgg_echo('elasticsearch:settings:type_boosting:title'), $boosting);
}


