<?php

use ColdTrick\ElasticSearch\Di\SearchService;

// form for inspect
$form_vars = [
	'method' => 'GET',
	'action' => 'admin/elasticsearch/inspect',
	'disable_security' => true,
];
echo elgg_view_form('elasticsearch/inspect', $form_vars);

// result listing
$result = '';

$guid = (int) get_input('guid');
if (empty($guid)) {
	return;
}

$registered_types = elasticsearch_get_registered_entity_types();

$entity = get_entity($guid);
if (empty($entity)) {
	// Entity doesn't exist in Elgg
	$result = elgg_view('output/longtext', [
		'value' => elgg_echo('notfound'),
	]);
} elseif (!array_key_exists($entity->getType(), $registered_types) || (array_key_exists($entity->getType(), $registered_types) && !empty($entity->getSubtype()) && !in_array($entity->getSubtype(), $registered_types[$entity->getType()]))) {
	// entity won't be exported to ES
	$result = elgg_view('output/longtext', [
		'value' => elgg_echo('elasticsearch:inspect:result:error:type_subtype'),
	]);
} else {
	// show inspect result
	elgg_push_context('search:index');
	$current_content = (array) $entity->toObject();
	elgg_pop_context();
	
	$last_indexed = $entity->getPrivateSetting(ELASTICSEARCH_INDEXED_NAME);
	if (is_null($last_indexed)) {
		$result = elgg_view('output/longtext', [
			'value' => elgg_echo('elasticsearch:inspect:result:last_indexed:none'),
		]);
	} elseif (empty($last_indexed)) {
		$result = elgg_view('output/longtext', [
			'value' => elgg_echo('elasticsearch:inspect:result:last_indexed:scheduled'),
		]);
	} else {
		$result = elgg_view('output/longtext', [
			'value' => elgg_echo('elasticsearch:inspect:result:last_indexed:time', [date('c', $last_indexed)]),
		]);
		$result .= elgg_view('output/url', [
			'icon' => 'refresh',
			'text' => elgg_echo('elasticsearch:inspect:result:reindex'),
			'href' => elgg_generate_action_url('elasticsearch/admin/reindex_entity', [
				'guid' => $entity->guid,
			]),
			'class' => 'elgg-button elgg-button-action',
		]);
	}
	
	$service = SearchService::instance();
	
	$elasticsearch_content = $service->inspect($guid);
	if (empty($elasticsearch_content)) {
		$result = elgg_view('output/longtext', [
			'value' => elgg_echo('elasticsearch:inspect:result:error:not_indexed'),
		]);
	} else {
		// add button to delete entity from index
		$result .= elgg_view('output/url', [
			'icon' => 'delete',
			'text' => elgg_echo('elasticsearch:inspect:result:delete'),
			'href' => elgg_generate_action_url('elasticsearch/admin/delete_entity', [
				'guid' => $entity->guid,
			]),
			'class' => 'elgg-button elgg-button-action',
		]);
		
		// needed for listing all possible values
		$merged = array_replace_recursive($current_content, $elasticsearch_content);
		
		$header = elgg_format_element('tr', [], implode(PHP_EOL, [
			elgg_format_element('th', [], '&nbsp'),
			elgg_format_element('th', [], elgg_echo('elasticsearch:inspect:result:elgg')),
			elgg_format_element('th', [], elgg_echo('elasticsearch:inspect:result:elasticsearch')),
		]));
		$header = elgg_format_element('thead', [], $header);
		
		$rows = [];
		$extras = [];
		foreach ($merged as $key => $values) {
			if (!is_array($values)) {
				// main content
				$elgg_value = elgg_extract($key, $current_content);
				if (is_array($elgg_value)) {
					$elgg_value = implode(', ', $elgg_value);
				}
				$es_value = elgg_extract($key, $elasticsearch_content);
				$class = [];
				if ($elgg_value != $es_value) {
					$class[] = 'elgg-state';
					$class[] = 'elgg-state-error';
				}
				$rows[] = elgg_format_element('tr', ['class' => $class], implode(PHP_EOL, [
					elgg_format_element('td', [], $key),
					elgg_format_element('td', [], $elgg_value),
					elgg_format_element('td', [], $es_value),
				]));
			} else {
				// has subvalues
				$subvalues = elasticsearch_inspect_show_values($key, $values, elgg_extract($key, $current_content), elgg_extract($key, $elasticsearch_content));
				if (!empty($subvalues)) {
					$extras = array_merge($extras, $subvalues);
				}
			}
		}
		
		$rows = array_merge($rows, $extras);
		
		$table_content = $header;
		$table_content .= elgg_format_element('tbody', [], implode(PHP_EOL, $rows));
		
		$result .= elgg_format_element('table', ['class' => ['elgg-table', 'elasticsearch-inspect-table']], $table_content);
	}
}

if (!empty($result)) {
	echo elgg_view_module('info', elgg_echo('elasticsearch:inspect:result:title'), $result);
}
