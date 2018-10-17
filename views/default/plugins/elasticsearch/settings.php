<?php

/* @var $plugin \ElggPlugin */
$plugin = elgg_extract('entity', $vars);

$noyes_options = [
	'no' => elgg_echo('option:no'),
	'yes' => elgg_echo('option:yes'),
];

// host configuration
$host = elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('elasticsearch:settings:host'),
	'#help' => elgg_echo('elasticsearch:settings:host:description'),
	'name' => 'params[host]',
	'value' => $plugin->host,
]);

$host .= elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('elasticsearch:settings:ignore_ssl'),
	'#help' => elgg_echo('elasticsearch:settings:ignore_ssl:description'),
	'name' => 'params[ignore_ssl]',
	'value' => 1,
	'checked' => !empty($plugin->ignore_ssl),
	'switch' => true,
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

echo elgg_view_module('info', elgg_echo('elasticsearch:settings:host:header'), $host);

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

echo elgg_view_module('info', elgg_echo('elasticsearch:settings:features:header'), $features);

// boosting of types
$types = elasticsearch_get_types_for_boosting();
if (!empty($types)) {
	
	$boosting = elgg_view('output/longtext', [
		'value' => elgg_echo('elasticsearch:settings:type_boosting:info'),
	]);
	
	// header row
	$row = [
		elgg_format_element('th', [], elgg_echo('elasticsearch:settings:type_boosting:type')),
		elgg_format_element('th', [], elgg_echo('elasticsearch:settings:type_boosting:multiplier')),
	];
	$header = elgg_format_element('thead', [], elgg_format_element('tr', [], implode(PHP_EOL, $row)));
	
	// content rows
	$rows = [];
	foreach ($types as $type) {
		$row = [];
		$setting_name = "type_boosting_{$type}";
		
		$label = $type;
		list($entity_type, $entity_subtype) = explode('.', $type);
		$key = implode(':', ['item', $entity_type, $entity_subtype]);
		if (elgg_language_key_exists($key)) {
			$label = elgg_echo($key);
			$label .= elgg_format_element('span', ['class' => 'elgg-subtext'], " ({$type})");
		}
		
		$row[] = elgg_format_element('td', [], $label);
		$row[] = elgg_format_element('td', [], elgg_view_field([
			'#type' => 'text',
			'#class' => 'man',
			'name' => "params[{$setting_name}]",
			'value' => $plugin->$setting_name,
		]));
		
		$rows[] = elgg_format_element('tr', [], implode(PHP_EOL, $row));
			
	}
	$boosting .= elgg_format_element('table', ['class' => 'elgg-table'], $header . elgg_format_element('tbody', [], implode(PHP_EOL, $rows)));

	echo elgg_view_module('info', elgg_echo('elasticsearch:settings:type_boosting:title'), $boosting);
}

$decay = elgg_view('output/longtext', ['value' => elgg_echo('elasticsearch:settings:decay:info')]);

$decay .= elgg_view_field([
	'#type' => 'number',
	'#label' => elgg_echo('elasticsearch:settings:decay_offset'),
	'#help' => elgg_echo('elasticsearch:settings:decay_offset:help'),
	'name' => 'params[decay_offset]',
	'value' => $plugin->decay_offset,
	'min' => 0,
]);

$decay .= elgg_view_field([
	'#type' => 'number',
	'#label' => elgg_echo('elasticsearch:settings:decay_scale'),
	'#help' => elgg_echo('elasticsearch:settings:decay_scale:help'),
	'name' => 'params[decay_scale]',
	'value' => $plugin->decay_scale,
	'min' => 0,
]);

$decay .= elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('elasticsearch:settings:decay_decay'),
	'#help' => elgg_echo('elasticsearch:settings:decay_decay:help'),
	'name' => 'params[decay_decay]',
	'value' => $plugin->decay_decay,
]);

echo elgg_view_module('info', elgg_echo('elasticsearch:settings:decay:title'), $decay);
