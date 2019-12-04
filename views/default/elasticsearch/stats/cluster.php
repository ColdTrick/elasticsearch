<?php

use ColdTrick\ElasticSearch\Di\IndexManagementService;

$service = elgg_extract('service', $vars);
if (!$service instanceof IndexManagementService) {
	return;
}

$alive = $service->ping();

$content = '<table class="elgg-table">';

$content .= '<tr>';
$content .= elgg_format_element('td', [], elgg_echo('status'));
if ($alive) {
	$content .= elgg_format_element('td', [], elgg_echo('ok'));
} else {
	$content .= elgg_format_element('td', [], elgg_echo('unknown_error'));
}
$content .= '</tr>';

if (!$alive) {
	$content .= '</table>';
	
	echo elgg_view_module('info', elgg_echo('elasticsearch:stats:cluster'), $content);
	return;
}

// get server info
$info = $service->getClusterInformation();

$content .= '<tr>';
$content .= elgg_format_element('td', [], elgg_echo('elasticsearch:stats:cluster_name'));
$content .= elgg_format_element('td', [], elgg_extract('cluster_name', $info));
$content .= '</tr>';

$content .= '<tr>';
$content .= elgg_format_element('td', [], elgg_echo('elasticsearch:stats:es_version'));
$content .= elgg_format_element('td', [], elgg_extract('number', elgg_extract('version', $info)));
$content .= '</tr>';

$content .= '<tr>';
$content .= elgg_format_element('td', [], elgg_echo('elasticsearch:stats:lucene_version'));
$content .= elgg_format_element('td', [], elgg_extract('lucene_version', elgg_extract('version', $info)));
$content .= '</tr>';

$content .= '</table>';

echo elgg_view_module('info', elgg_echo('elasticsearch:stats:cluster'), $content);
