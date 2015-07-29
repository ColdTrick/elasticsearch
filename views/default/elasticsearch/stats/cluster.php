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

echo '<table class="elgg-table">';

echo '<tr>';
echo '<td>' . elgg_echo('status') . '</td>';
if ($alive) {
	echo '<td>' . elgg_echo('ok') . '</td>';
} else {
	echo '<td>' . elgg_echo('unknown_error') . '</td>';
}
echo '</tr>';

if (!$alive) {
	echo '</table>';
	return;
}

// get server info
$info = $client->info();

echo '<tr>';
echo '<td>' . elgg_echo('elasticsearch:stats:cluster_name') . '</td>';
echo '<td>' . elgg_extract('cluster_name', $info) . '</td>';
echo '</tr>';

echo '<tr>';
echo '<td>' . elgg_echo('elasticsearch:stats:es_version') . '</td>';
echo '<td>' . elgg_extract('number', elgg_extract('version', $info)) . '</td>';
echo '</tr>';

echo '<tr>';
echo '<td>' . elgg_echo('elasticsearch:stats:lucene_version') . '</td>';
echo '<td>' . elgg_extract('lucene_version', elgg_extract('version', $info)) . '</td>';
echo '</tr>';

echo '</table>';