<?php

elgg_admin_gatekeeper();

$logging_base_dir = elgg_get_data_path() . 'elasticsearch/';
$path = get_input('path');
$path = sanitise_filepath($path, false);

$logging_file = $logging_base_dir . $path;

if (!is_file($logging_file)) {
	echo elgg_echo('notfound');
	return;
}

$content = nl2br(file_get_contents($logging_file));

echo elgg_format_element('div', array('style' => 'max-height: 600px; scroll:auto; width: 100000000px;'), $content);
