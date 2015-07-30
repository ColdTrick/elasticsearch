<?php

$content = '<table class="elgg-table">';

// sync enabled
$sync_enabled = elgg_get_plugin_setting('sync', 'elasticsearch', 'no');
$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:settings:sync') . '</td>';
$content .= '<td>' . elgg_echo("option:{$sync_enabled}") . '</td>';
$content .= '</tr>';

// content to index
$options = elasticsearch_get_bulk_options('count');
$count = elgg_get_entities($options);

$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:stats:elgg:total') . '</td>';
$content .= "<td>{$count}</td>";
$content .= '</tr>';

// content to index
$options = elasticsearch_get_bulk_options();
$options['count'] = true;
$count = elgg_get_entities($options);

$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:stats:elgg:no_index_ts') . '</td>';
$content .= "<td>{$count}</td>";
$content .= '</tr>';

// content to update
$options = elasticsearch_get_bulk_options('update');
$options['count'] = true;
$count = elgg_get_entities_from_private_settings($options);

$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:stats:elgg:update') . '</td>';
$content .= "<td>{$count}</td>";
$content .= '</tr>';

// content to reindex
$options = elasticsearch_get_bulk_options('reindex');
$count = 0;
if (!empty($options)) {
	$options['count'] = true;
	$count = elgg_get_entities_from_private_settings($options);
}

$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:stats:elgg:reindex') . '</td>';
$content .= "<td>{$count}</td>";
$content .= '</tr>';

$content .= '</table>';

echo elgg_view_module('inline', elgg_echo('elasticsearch:stats:elgg'), $content);
