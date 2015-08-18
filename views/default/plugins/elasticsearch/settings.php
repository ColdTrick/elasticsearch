<?php

$plugin = elgg_extract('entity', $vars);

$noyes_options = array(
	'no' => elgg_echo('option:no'),
	'yes' => elgg_echo('option:yes'),
);

// host configuration
$host = '<div>';
$host .= elgg_echo('elasticsearch:settings:host');
$host .= elgg_view('input/text', array(
	'name' => 'params[host]',
	'value' => $plugin->host,
));
$host .= '<div class="elgg-subtext">' . elgg_echo('elasticsearch:settings:host:description') . '</div>';
$host .= '</div>';

$host .= '<div>';
$host .= elgg_echo('elasticsearch:settings:index');
$host .= elgg_view('input/text', array(
	'name' => 'params[index]',
	'value' => $plugin->index,
));
$host .= '<div class="elgg-subtext">' . elgg_echo('elasticsearch:settings:index:suggestion', array(elgg_get_config('dbname'))) . '</div>';
$host .= '</div>';

$host .= '<div>';
$host .= elgg_echo('elasticsearch:settings:search_alias');
$host .= elgg_view('input/text', array(
	'name' => 'params[search_alias]',
	'value' => $plugin->search_alias,
));
$host .= '<div class="elgg-subtext">' . elgg_echo('elasticsearch:settings:search_alias:description') . '</div>';
$host .= '</div>';

echo elgg_view_module('inline', elgg_echo('elasticsearch:settings:host:header'), $host);

// features
$features = '<div>';
$features .= elgg_echo('elasticsearch:settings:sync');
$features .= elgg_view('input/select', array(
	'name' => 'params[sync]',
	'value' => $plugin->sync,
	'options_values' => $noyes_options,
	'class' => 'mls',
));
$features .= '<div class="elgg-subtext">' . elgg_echo('elasticsearch:settings:sync:description') . '</div>';
$features .= '</div>';

$features .= '<div>';
$features .= elgg_echo('elasticsearch:settings:search');
$features .= elgg_view('input/select', array(
	'name' => 'params[search]',
	'value' => $plugin->search,
	'options_values' => $noyes_options,
	'class' => 'mls',
));
$features .= '<div class="elgg-subtext">' . elgg_echo('elasticsearch:settings:search:description') . '</div>';
$features .= '</div>';

echo elgg_view_module('inline', elgg_echo('elasticsearch:settings:features:header'), $features);