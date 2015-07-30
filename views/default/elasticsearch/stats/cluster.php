<?php

$client = elgg_extract('client', $vars);
if (empty($client) || !($client instanceof \ColdTrick\ElasticSearch\Client)) {
	return;
}

$alive = false;
try {
	$alive = $client->ping();
} catch (Exception $e) {

}

$content = '<table class="elgg-table">';

$content .= '<tr>';
$content .= '<td>' . elgg_echo('status') . '</td>';
if ($alive) {
	$content .= '<td>' . elgg_echo('ok') . '</td>';
} else {
	$content .= '<td>' . elgg_echo('unknown_error') . '</td>';
}
$content .= '</tr>';

if (!$alive) {
	$content .= '</table>';
	
	echo elgg_view_module('inline', elgg_echo('elasticsearch:stats:cluster'), $content);
	return;
}

// get server info
$info = $client->info();

$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:stats:cluster_name') . '</td>';
$content .= '<td>' . elgg_extract('cluster_name', $info) . '</td>';
$content .= '</tr>';

$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:stats:es_version') . '</td>';
$content .= '<td>' . elgg_extract('number', elgg_extract('version', $info)) . '</td>';
$content .= '</tr>';

$content .= '<tr>';
$content .= '<td>' . elgg_echo('elasticsearch:stats:lucene_version') . '</td>';
$content .= '<td>' . elgg_extract('lucene_version', elgg_extract('version', $info)) . '</td>';
$content .= '</tr>';

$content .= '</table>';

echo elgg_view_module('inline', elgg_echo('elasticsearch:stats:cluster'), $content);