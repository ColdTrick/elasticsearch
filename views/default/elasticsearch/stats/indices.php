<?php

$client = elgg_extract('client', $vars);
if (empty($client) || !($client instanceof \ColdTrick\ElasticSearch\Client)) {
	return;
}

try {
	$client->ping();
} catch (Exception $e) {
	// server down
	return;
}

try {
	$stats = $client->indices()->stats();
} catch (Exception $e) {
	return;
}

$indices = elgg_extract('indices', $stats);
if (empty($indices)) {
	return;
}

foreach ($indices as $index => $index_stats) {
	
	$content = '<thead>';
	$content .= '<tr>';
	$content .= '<th>' . elgg_echo('elasticsearch:stats:index:stat') . '</th>';
	$content .= '<th>' . elgg_echo('elasticsearch:stats:index:value') . '</th>';
	$content .= '</tr>';
	$content .= '</thead>';
	
	$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($index_stats), RecursiveIteratorIterator::SELF_FIRST);
	$rows = [];
	foreach ($it as $key => $value) {
		$row = [];
		
		$key = str_repeat('&nbsp;&nbsp;&nbsp;', $it->getDepth()) . $key;
		
		if ($it->callHasChildren()) {
			$row[] = elgg_format_element('td', ['colspan' => 2], elgg_format_element('b', [], $key));
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
