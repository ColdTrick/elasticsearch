<?php

$result = elgg_extract('result', $vars);

if (!$result) {
	return;
}

$hits = elgg_extract('hits', $result);
unset($result['hits']);

// general stats
$content = '<table class="elgg-table">';

$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($result), RecursiveIteratorIterator::SELF_FIRST);
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
$content .= '</table>';

echo $content;

echo '<br />';

// hits
$content = '<table class="elgg-table">';

$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($hits), RecursiveIteratorIterator::SELF_FIRST);
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
$content .= '</table>';
echo $content;
