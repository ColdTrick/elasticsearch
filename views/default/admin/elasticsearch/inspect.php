<?php

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
if (!empty($guid)) {
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
				'text' => elgg_echo('elasticsearch:inspect:result:reindex'),
				'href' => "action/elasticsearch/admin/reindex_entity?guid={$entity->getGUID()}",
				'is_action' => true,
				'class' => 'elgg-button elgg-button-action',
			]);
		}
		
		$client = elasticsearch_get_client();
		$elasticsearch_content = $client->inspect($guid);
		if (empty($elasticsearch_content)) {
			$result = elgg_view('output/longtext', [
				'value' => elgg_echo('elasticsearch:inspect:result:error:not_indexed'),
			]);
		} else {
			// add button to delete entity from index
			$result .= elgg_view('output/url', [
				'text' => elgg_echo('elasticsearch:inspect:result:delete'),
				'href' => "action/elasticsearch/admin/delete_entity?guid={$entity->getGUID()}",
				'is_action' => true,
				'class' => 'elgg-button elgg-button-action',
			]);
			
			// needed for listing all possible values
			$merged = array_replace_recursive($current_content, $elasticsearch_content);
			
			$rows = [];
			$extras = [];
			$rows[] = implode(PHP_EOL, [
				elgg_format_element('th', [], '&nbsp'),
				elgg_format_element('th', [], elgg_echo('elasticsearch:inspect:result:elgg')),
				elgg_format_element('th', [], elgg_echo('elasticsearch:inspect:result:elasticsearch')),
			]);
			
			foreach ($merged as $key => $values) {
				if (!is_array($values)) {
					// main content
					$rows[] = implode(PHP_EOL, [
						elgg_format_element('td', [], $key),
						elgg_format_element('td', [], elgg_extract($key, $current_content)),
						elgg_format_element('td', [], elgg_extract($key, $elasticsearch_content)),
					]);
				} else {
					// has subvalues
					$subvalues = elasticsearch_inspect_show_values($key, $values, elgg_extract($key, $current_content), elgg_extract($key, $elasticsearch_content));
					if (!empty($subvalues)) {
						$extras = array_merge($extras, $subvalues);
					}
				}
			}
			
			$rows = array_merge($rows, $extras);
			
			$result .= elgg_format_element('table', ['class' => 'elgg-table'], '<tr>' . implode('</tr><tr>', $rows) . '</tr>');
		}
	}
}

if (!empty($result)) {
	echo elgg_view_module('inline', elgg_echo('elasticsearch:inspect:result:title'), $result);
}
