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
echo elgg_format_element('th', ['class' => 'center'], elgg_echo('elasticsearch:indices:mappings'));
echo elgg_format_element('th', ['class' => 'center'], elgg_echo('elasticsearch:indices:alias'));
echo elgg_format_element('th', ['class' => 'center'], elgg_echo('delete'));
echo '</tr>';
echo '</thead>';

// begin content
$rows = [];
foreach ($indices as $name => $status) {
	$cells = [];
	$current = false;
	$alias_configured = false;
	
	$aliases = $service->getAliases($name);
	
	if ($name === $elgg_index) {
		$elgg_index_found = true;
		$current = true;
	}
	
	if (!empty($search_alias) && in_array($search_alias, $aliases)) {
		$alias_configured = true;
	}
	
	// index name
	$output_name = $name;
	if ($current) {
		$output_name = elgg_format_element('strong', [], $output_name);
	}
	
	if (!empty($aliases)) {
		$output_name .= ' [' . elgg_echo('elasticsearch:indices:aliases') . ': ' . implode(', ', $aliases) . ']';
	}
	
	$cells[] = elgg_format_element('td', [], $output_name);
	
	// add mappings
	$mapping = '&nbsp;';
	if ($current) {
		$mapping = elgg_view('output/url', [
			'icon' => 'plus',
			'text' => elgg_echo('elasticsearch:indices:mappings:add'),
			'href' => elgg_generate_action_url('elasticsearch/admin/index_management', [
				'task' => 'add_mappings',
				'index' => $name,
			]),
			'class' => 'elgg-button elgg-button-action',
			'confirm' => true,
		]);
	}
	
	$cells[] = elgg_format_element('td', ['class' => 'center'], $mapping);
	
	// add alias
	$alias = '&nbsp;';
	if (!empty($search_alias) && !$alias_configured) {
		$alias = elgg_view('output/url', [
			'icon' => 'plus',
			'text' => elgg_echo('add'),
			'href' => elgg_generate_action_url('elasticsearch/admin/index_management', [
				'task' => 'add_alias',
				'index' => $name,
			]),
			'class' => 'elgg-button elgg-button-action',
			'confirm' => true,
		]);
	} elseif (!empty($search_alias) && $alias_configured) {
		$alias = elgg_view('output/url', [
			'icon' => 'delete',
			'text' => elgg_echo('delete'),
			'href' => elgg_generate_action_url('elasticsearch/admin/index_management', [
				'task' => 'delete_alias',
				'index' => $name,
			]),
			'class' => 'elgg-button elgg-button-delete',
			'confirm' => true,
		]);
	}
	
	$cells[] = elgg_format_element('td', ['class' => 'center'], $alias);
	
	// delete
	$cells[] = elgg_format_element('td', ['class' => 'center'], elgg_view('output/url', [
		'icon' => 'delete',
		'text' => elgg_echo('delete'),
		'href' => elgg_generate_action_url('elasticsearch/admin/index_management', [
			'task' => 'delete',
			'index' => $name,
		]),
		'class' => 'elgg-button elgg-button-delete',
		'confirm' => true,
	]));
	
	$rows[] = elgg_format_element('tr', [], implode(PHP_EOL, $cells));
}

echo elgg_format_element('tbody', [], implode(PHP_EOL, $rows));
// end content
echo '</table>';

if (!$elgg_index_found) {
	elgg_register_menu_item('title', [
		'name' => 'add',
		'icon' => 'plus',
		'text' => elgg_echo('create'),
		'href' => elgg_generate_action_url('elasticsearch/admin/index_management', [
			'task' => 'create',
			'index' => $elgg_index,
		]),
		'link_class' => 'elgg-button elgg-button-action',
		'confirm' => true,
	]);
}
