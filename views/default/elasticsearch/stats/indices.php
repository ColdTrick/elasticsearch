<?php

use ColdTrick\ElasticSearch\Di\IndexManagementService;

$service = elgg_extract('service', $vars);
if (!$service instanceof IndexManagementService) {
	return;
}

if (!$service->ping()) {
	// server down
	return;
}

$stats = $service->getIndexStatus();
if (empty($stats)) {
	// no indexes on server
	return;
}

foreach ($stats as $index => $index_stats) {
	
	$content = '<thead>';
	$content .= '<tr>';
	$content .= elgg_format_element('th', [], elgg_echo('elasticsearch:stats:index:stat'));
	$content .= elgg_format_element('th', [], elgg_echo('elasticsearch:stats:index:value'));
	$content .= '</tr>';
	$content .= '</thead>';
	
	$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($index_stats), RecursiveIteratorIterator::SELF_FIRST);
	$rows = [];
	foreach ($it as $key => $value) {
		$row = [];
		
		$key = str_repeat('&nbsp;&nbsp;&nbsp;', $it->getDepth()) . $key;
		
		if ($it->callHasChildren()) {
			$row[] = elgg_format_element('td', ['colspan' => 2], elgg_format_element('strong', [], $key));
		} else {
			$row[] = elgg_format_element('td', [], $key);
			$row[] = elgg_format_element('td', [], $value);
		}
		
		$rows[] = elgg_format_element('tr', [], implode(PHP_EOL, $row));
	}
	
	if (!empty($rows)) {
		$content .= elgg_format_element('tbody', [], implode(PHP_EOL, $rows));
	}
	
	$content = elgg_format_element('table', ['class' => 'elgg-table hidden', 'id' => "index_{$index}"], $content);
	
	$title = elgg_view('output/url', [
		'text' => elgg_echo('elasticsearch:stats:index:index', [$index]),
		'href' => "#index_{$index}",
		'rel' => 'toggle',
	]);
	
	echo elgg_view_module('info', $title, $content);
}
