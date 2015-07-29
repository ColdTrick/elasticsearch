<?php

$plugin = elgg_extract('entity', $vars);

echo '<div>';
echo elgg_echo('elasticsearch:settings:host');
echo elgg_view('input/text', array(
	'name' => 'params[host]',
	'value' => $plugin->host,
));
echo '</div>';

echo '<div>';
echo elgg_echo('elasticsearch:settings:index');
echo elgg_view('input/text', array(
	'name' => 'params[index]',
	'value' => $plugin->index,
));
echo '<div class="elgg-subtext">' . elgg_echo('elasticsearch:settings:index:suggestion', array(elgg_get_config('dbname'))) . '</div>';
echo '</div>';
