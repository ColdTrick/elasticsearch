<?php

elgg_load_css('elgg.icons');

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

$elgg_index = elasticsearch_get_setting('index');
$search_alias = elasticsearch_get_setting('search_alias');
$elgg_index_found = false;

try {
	$status = $client->indices()->status();
} catch (Exception $e){}

$indices = elgg_extract('indices', $status);

echo '<table class="elgg-table">';

echo '<tr>';
echo '<th>' . elgg_echo('elasticsearch:indices:index') . '</th>';
echo '<th class="center">' . elgg_echo('elasticsearch:indices:create') . '</th>';
echo '<th class="center">' . elgg_echo('elasticsearch:indices:alias') . '</th>';
echo '<th class="center">' . elgg_echo('delete') . '</th>';
echo '<th class="center">' . elgg_echo('elasticsearch:indices:optimize') . '</th>';
echo '<th class="center">' . elgg_echo('elasticsearch:indices:flush') . '</th>';
echo '</tr>';

foreach ($indices as $name => $status) {
	$current = false;
	$alias_configured = false;
	
	if ($name === $elgg_index) {
		$elgg_index_found = true;
		$current = true;
	}
	
	if (!empty($search_alias) && $client->indices()->existsAlias(array('index' => $name, 'name' => $search_alias))) {
		$alias_configured = true;
	}
	
	echo '<tr>';
	if ($current) {
		echo '<td><b>' . $name . '</b></td>';
	} else {
		echo '<td>' . $name . '</td>';
	}
	// create
	echo '<td>&nbsp;</td>';
	// add alias
	if (!empty($search_alias) && !$alias_configured) {
		echo '<td class="center">' . elgg_view('output/url', array(
			'text' => elgg_view_icon('round-plus'),
			'href' => "action/elasticsearch/admin/index_management?task=add_alias&index={$name}",
			'confirm' => true,
		)) . '</td>';
	} elseif (!empty($search_alias) && $alias_configured) {
		echo '<td class="center">' . elgg_view('output/url', array(
			'text' => elgg_view_icon('delete-alt'),
			'href' => "action/elasticsearch/admin/index_management?task=delete_alias&index={$name}",
			'confirm' => true,
		)) . '</td>';
	} else {
		echo '<td>&nbsp;</td>';
	}
	// delete
	echo '<td class="center">' . elgg_view('output/url', array(
		'text' => elgg_view_icon('delete-alt'),
		'href' => "action/elasticsearch/admin/index_management?task=delete&index={$name}",
		'confirm' => true,
	)) . '</td>';
	// optimize
	echo '<td class="center">' . elgg_view('output/url', array(
		'text' => elgg_view_icon('refresh'),
		'href' => "action/elasticsearch/admin/index_management?task=optimize&index={$name}",
		'confirm' => true,
	)) . '</td>';
	// flush
	echo '<td class="center">' . elgg_view('output/url', array(
		'text' => elgg_view_icon('round-checkmark'),
		'href' => "action/elasticsearch/admin/index_management?task=flush&index={$name}",
		'confirm' => true,
	)) . '</td>';
	echo '</tr>';
	
}

if (!$elgg_index_found) {
	echo '<tr>';
	echo '<td><b>' . $elgg_index . '</b></td>';
	echo '<td class="center">' . elgg_view('output/url', array(
		'text' => elgg_view_icon('round-plus'),
		'href' => "action/elasticsearch/admin/index_management?task=create&index={$elgg_index}",
		'confirm' => true,
	)) . '</td>';
	echo '<td>&nbsp;</td>';
	echo '<td>&nbsp;</td>';
	echo '<td>&nbsp;</td>';
	echo '<td>&nbsp;</td>';
	echo '</tr>';
}

echo '</table>';