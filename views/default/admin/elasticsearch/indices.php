<?php

$client = elasticsearch_get_client();
if (empty($client)) {
	echo elgg_echo('elasticsearch:error:no_client');
	return;
}

// check if server is up
$alive = false;
try {
	$alive = $client->ping();
} catch (Exception $e) {
	
}

if (!$alive) {
	echo elgg_echo('elasticsearch:error:host_unavailable');
	return;
}

$elgg_index = elgg_get_plugin_setting('index', 'elasticsearch');
$elgg_index_found = false;

try {
	$status = $client->indices()->status();
} catch (Exception $e){}

$indices = elgg_extract('indices', $status);

echo '<table class="elgg-table">';

echo '<tr>';
echo '<th>' . elgg_echo('elasticsearch:indices:index') . '</th>';
echo '<th class="center">' . elgg_echo('elasticsearch:indices:create') . '</th>';
echo '<th class="center">' . elgg_echo('delete') . '</th>';
echo '<th class="center">' . elgg_echo('elasticsearch:indices:optimize') . '</th>';
echo '<th class="center">' . elgg_echo('elasticsearch:indices:flush') . '</th>';
echo '</tr>';

foreach ($indices as $name => $status) {
	$current = false;
	if ($name === $elgg_index) {
		$elgg_index_found = true;
		$current = true;
	}
	
	echo '<tr>';
	if ($current) {
		echo '<td><b>' . $name . '</b></td>';
	} else {
		echo '<td>' . $name . '</td>';
	}
	echo '<td>&nbsp;</td>';
	echo '<td class="center">' . elgg_view('output/url', array(
		'text' => elgg_view_icon('delete-alt'),
		'href' => "action/elasticsearch/admin/index_management?task=delete&index={$name}",
		'confirm' => true,
	)) . '</td>';
	echo '<td class="center">' . elgg_view('output/url', array(
		'text' => elgg_view_icon('refresh'),
		'href' => "action/elasticsearch/admin/index_management?task=optimize&index={$name}",
		'confirm' => true,
	)) . '</td>';
	echo '<td class="center">' . elgg_view('output/url', array(
		'text' => elgg_view_icon('round-checkmark'),
		'href' => "action/elasticsearch/admin/index_management?task=flush&index={$name}",
		'confirm' => true,
	)) . '</td>';
	echo '</tr>';
	
}

if (!$elgg_index_found) {
	echo '<tr>';
	echo '<td>' . $elgg_index . '</td>';
	echo '<td class="center">' . elgg_view('output/url', array(
		'text' => elgg_view_icon('star-empty'),
		'href' => "action/elasticsearch/admin/index_management?task=create&index={$elgg_index}",
		'confirm' => true,
	)) . '</td>';
	echo '<td>&nbsp;</td>';
	echo '<td>&nbsp;</td>';
	echo '<td>&nbsp;</td>';
	echo '</tr>';
}

echo '</table>';