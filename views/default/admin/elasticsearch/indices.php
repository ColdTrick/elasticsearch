<?php

use ColdTrick\ElasticSearch\Di\IndexManagementService;

$service = IndexManagementService::instance();

if (!$service->isClientReady()) {
	echo elgg_echo('elasticsearch:error:no_client');
	return;
}

// check if server is up
if (!$service->ping()) {
	echo elgg_echo('elasticsearch:error:host_unavailable');
	return;
}

$elgg_index = elgg_get_plugin_setting('index', 'elasticsearch');
$search_alias = elgg_get_plugin_setting('search_alias', 'elasticsearch');
$elgg_index_found = false;

$indices = $service->getIndexStatus();

echo '<table class="elgg-table">';

echo '<thead>';
echo '<tr>';
echo elgg_format_element('th', [], elgg_echo('elasticsearch:indices:index'));
echo elgg_format_element('th', ['class' => 'center'], elgg_echo('elasticsearch:indices:create'));
echo elgg_format_element('th', ['class' => 'center'], elgg_echo('elasticsearch:indices:add_mappings'));
echo elgg_format_element('th', ['class' => 'center'], elgg_echo('elasticsearch:indices:alias'));
echo elgg_format_element('th', ['class' => 'center'], elgg_echo('delete'));
echo elgg_format_element('th', ['class' => 'center'], elgg_echo('elasticsearch:indices:flush'));
echo '</tr>';
echo '</thead>';

// begin content
$rows = [];
foreach ($indices as $name => $status) {
	$cells = [];
	$current = false;
	$alias_configured = false;
	
	if ($name === $elgg_index) {
		$elgg_index_found = true;
		$current = true;
	}
	
	if (!empty($search_alias) && $service->indexHasAlias($name, $search_alias)) {
		$alias_configured = true;
	}
	
	// index name
	$output_name = $name;
	if ($current) {
		$output_name = elgg_format_element('strong', [], $output_name);
	}
	
	$cells[] = elgg_format_element('td', [], $output_name);
	
	// create
	$cells[] = elgg_format_element('td', ['class' => 'center'], '&nbsp;');
	
	// add mappings
	$mapping = '&nbsp;';
	if ($current) {
		$mapping = elgg_view('output/url', [
			'icon' => 'round-plus',
			'text' => false,
			'href' => elgg_generate_action_url('elasticsearch/admin/index_management', [
				'task' => 'add_mappings',
				'index' => $name,
			]),
			'confirm' => true,
		]);
	}
	
	$cells[] = elgg_format_element('td', ['class' => 'center'], $mapping);
	
	// add alias
	$alias = '&nbsp;';
	if (!empty($search_alias) && !$alias_configured) {
		$alias = elgg_view('output/url', [
			'icon' => 'round-plus',
			'text' => false,
			'href' => elgg_generate_action_url('elasticsearch/admin/index_management', [
				'task' => 'add_alias',
				'index' => $name,
			]),
			'confirm' => true,
		]);
	} elseif (!empty($search_alias) && $alias_configured) {
		$alias = elgg_view('output/url', [
			'icon' => 'delete-alt',
			'text' => false,
			'href' => elgg_generate_action_url('elasticsearch/admin/index_management', [
				'task' => 'delete_alias',
				'index' => $name,
			]),
			'confirm' => true,
		]);
	}
	
	$cells[] = elgg_format_element('td', ['class' => 'center'], $alias);
	
	// delete
	$cells[] = elgg_format_element('td', ['class' => 'center'], elgg_view('output/url', [
		'icon' => 'delete-alt',
		'text' => false,
		'href' => elgg_generate_action_url('elasticsearch/admin/index_management', [
			'task' => 'delete',
			'index' => $name,
		]),
		'confirm' => true,
	]));
	
	// flush
	$cells[] = elgg_format_element('td', ['class' => 'center'], elgg_view('output/url', [
		'icon' => 'round-checkmark',
		'text' => false,
		'href' => elgg_generate_action_url('elasticsearch/admin/index_management', [
			'task' => 'flush',
			'index' => $name,
		]),
		'confirm' => true,
	]));
	
	$rows[] = elgg_format_element('tr', [], implode(PHP_EOL, $cells));
}

echo elgg_format_element('tbody', [], implode(PHP_EOL, $rows));
// end content

if (!$elgg_index_found) {
	echo '<tfoot>';
	echo '<tr>';
	echo elgg_format_element('td', [], elgg_format_element('strong', [], $elgg_index));
	echo elgg_format_element('td', ['class' => 'center'], elgg_view('output/url', [
		'icon' => 'round-plus',
		'text' => false,
		'href' => elgg_generate_action_url('elasticsearch/admin/index_management', [
			'task' => 'create',
			'index' => $elgg_index,
		]),
		'confirm' => true,
	]));
	echo elgg_format_element('td', ['class' => 'center', 'colspan' => 4], '&nbsp;');
	echo '</tr>';
	echo '</tfoot>';
}

echo '</table>';
