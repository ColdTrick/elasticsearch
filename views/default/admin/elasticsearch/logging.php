<?php

use Elgg\Project\Paths;

$logging_base_dir = elgg_get_data_path() . 'elasticsearch/';
$path = get_input('path');

$logging_dir = $logging_base_dir . $path;

echo elgg_view('output/longtext', [
	'value' => elgg_echo('elasticsearch:logging:description'),
]);

if (!empty($path)) {
	$path = Paths::sanitize($path);
	
	$parts = explode('/', trim($path, '/'));
	
	$urls = [];
	$urls[] = elgg_view('output/url', [
		'text' => elgg_echo('elasticsearch:logging:root'),
		'href' => "admin/elasticsearch/logging",
		'is_trusted' => true,
	]);
	
	$new_path = '';
	foreach ($parts as $part) {
		$new_path .= "{$part}/";
		
		$urls[] = elgg_view('output/url', [
			'text' => $part,
			'href' => elgg_http_add_url_query_elements('admin/elasticsearch/logging', [
				'path' => $new_path,
			]),
			'is_trusted' => true,
		]);
	}
	
	echo elgg_format_element('div', ['class' => 'mbs'], implode(' > ', $urls));
}

try {
	$flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS;
	$fs_it = new FilesystemIterator($logging_dir, $flags);
} catch (Exception $e) {
	echo elgg_echo('notfound');
	return;
}

$lis = [];

foreach ($fs_it as $file => $info) {
	
	$basename = $info->getBasename();
	$path = str_ireplace($logging_base_dir, '', $file);
	
	if ($fs_it->isDir()) {
		$li = elgg_view('output/url', [
			'icon' => 'list',
			'text' => $basename,
			'href' => elgg_http_add_url_query_elements('admin/elasticsearch/logging', [
				'path' => $path,
			]),
			'is_trusted' => true,
			'class' => 'mls',
		]);
	} else {
		$li = elgg_view('output/url', [
			'icon' => 'download',
			'title' => elgg_echo('download'),
			'text' => $basename,
			'href' => elgg_generate_action_url('elasticsearch/admin/download_log', [
				'path' => $path,
			]),
			'is_trusted' => true,
		]);
	}
	
	$lis[] = elgg_format_element('li', [], $li);
}

if (!empty($lis)) {
	echo elgg_format_element('ul', [], implode(PHP_EOL, $lis));
} else {
	echo elgg_echo('notfound');
}
