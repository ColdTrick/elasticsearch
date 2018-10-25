<?php

$content = '<table class="elgg-table">';

// sync enabled
$sync_enabled = elgg_get_plugin_setting('sync', 'elasticsearch');
$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:settings:sync') . '</td>';
$content .= '<td>' . elgg_echo("option:{$sync_enabled}") . '</td>';
$content .= '</tr>';

// content to index
$options = elasticsearch_get_bulk_options('count');
$count = elgg_get_entities($options);

$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:stats:elgg:total');
$content .= elgg_view('output/longtext', [
	'value' => elgg_echo('elasticsearch:stats:elgg:total:help'),
	'class' => 'elgg-subtext',
]) . '</td>';
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
$count = elgg_get_entities($options);

$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:stats:elgg:update') . '</td>';
$content .= "<td>{$count}</td>";
$content .= '</tr>';

// content to reindex
$options = elasticsearch_get_bulk_options('reindex');
$count = 0;
if (!empty($options)) {
	$options['count'] = true;
	$count = elgg_get_entities($options);
}

$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:stats:elgg:reindex');

$reindex_title = elgg_echo('elasticsearch:stats:elgg:reindex:action');
$last_ts = (int) elgg_get_plugin_setting('reindex_ts', 'elasticsearch');

if (!empty($last_ts)) {
	$reindex_title .= '&#10;&#10;' . elgg_echo('elasticsearch:stats:elgg:reindex:last_ts', [date('c', $last_ts)]);
}

$content .= elgg_view('output/url', [
	'confirm' => true,
	'href' => 'action/elasticsearch/admin/reindex',
	'text' => elgg_view_icon('refresh'),
	'title' => $reindex_title,
	'class' => 'mlm'
]);

$content .= '</td>';
$content .= "<td>{$count}</td>";
$content .= '</tr>';

$count = count(elasticsearch_get_documents_for_deletion());

$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:stats:elgg:delete') . '</td>';
$content .= "<td>{$count}</td>";
$content .= '</tr>';

$content .= '</table>';

echo elgg_view_module('info', elgg_echo('elasticsearch:stats:elgg'), $content);
