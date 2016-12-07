<?php

echo elgg_view_input('text', [
	'label' => elgg_echo('elasticsearch:inspect:guid'),
	'help' => elgg_echo('elasticsearch:inspect:guid:help'),
	'name' => 'guid',
	'value' => get_input('guid'),
]);

// build footer
$footer = elgg_view_input('submit', [
	'value' => elgg_echo('elasticsearch:inspect:submit'),
]);

echo elgg_format_element('div', ['class' => 'elgg-foot'], $footer);
