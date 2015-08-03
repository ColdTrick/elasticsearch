<?php

$plugin = elgg_extract('entity', $vars);

$noyes_options = array(
	'no' => elgg_echo('option:no'),
	'yes' => elgg_echo('option:yes'),
);

echo '<div>';
echo elgg_echo('elasticsearch:settings:host');
echo elgg_view('input/text', array(
	'name' => 'params[host]',
	'value' => $plugin->host,
));
echo '<div class="elgg-subtext">' . elgg_echo('elasticsearch:settings:host:description') . '</div>';
echo '</div>';

echo '<div>';
echo elgg_echo('elasticsearch:settings:index');
echo elgg_view('input/text', array(
	'name' => 'params[index]',
	'value' => $plugin->index,
));
echo '<div class="elgg-subtext">' . elgg_echo('elasticsearch:settings:index:suggestion', array(elgg_get_config('dbname'))) . '</div>';
echo '</div>';

echo '<div>';
echo elgg_echo('elasticsearch:settings:search_alias');
echo elgg_view('input/text', array(
	'name' => 'params[search_alias]',
	'value' => $plugin->search_alias,
));
echo '<div class="elgg-subtext">' . elgg_echo('elasticsearch:settings:search_alias:description') . '</div>';
echo '</div>';

echo '<div>';
echo elgg_echo('elasticsearch:settings:sync');
echo elgg_view('input/select', array(
	'name' => 'params[sync]',
	'value' => $plugin->sync,
	'options_values' => $noyes_options,
	'class' => 'mls',
));
echo '<div class="elgg-subtext">' . elgg_echo('elasticsearch:settings:sync:description') . '</div>';
echo '</div>';
