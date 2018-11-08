<?php

$result = elgg_extract('result', $vars);

if (!$result) {
	return;
}

$hits = elgg_extract('hits', $result);
unset($result['hits']);

$format_table = function($results) {
	$rows = [];
	$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($results), RecursiveIteratorIterator::SELF_FIRST);
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
	
	return elgg_format_element('table', ['class' => ['elgg-table', 'mbl', 'elasticsearch-inspect-table']], implode(PHP_EOL, $rows));
};

// general stats
echo $format_table($result);

// hits
echo $format_table($hits);
