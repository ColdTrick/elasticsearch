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
	
	$content = '<tr>';
	$content .= '<th>' . elgg_echo('elasticsearch:stats:index:stat') . '</th>';
	$content .= '<th>' . elgg_echo('elasticsearch:stats:index:value') . '</th>';
	$content .= '</tr>';
	
	$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($index_stats), RecursiveIteratorIterator::SELF_FIRST);
	foreach ($it as $key => $value) {
		
		$key = str_repeat('&nbsp;&nbsp;&nbsp;', $it->getDepth()) . $key;
		$content .= '<tr>';
		if ($it->callHasChildren()) {
			$content .= '<td colspan="2"><b>' . $key . '</b></td>';
		} else {
			$content .= '<td>' . $key . '</td>';
			$content .= '<td>' . $value . '</td>';
		}
		$content .= '</tr>';
	}
	
	$content = elgg_format_element('table', array('class' => 'elgg-table hidden', 'id' => "index_{$index}"), $content);
	
	$title = elgg_view('output/url', array(
		'text' => elgg_echo('elasticsearch:stats:index:index', array($index)),
		'href' => "#index_{$index}",
		'rel' => 'toggle',
	));
	
	echo elgg_view_module('inline', $title, $content);
}
