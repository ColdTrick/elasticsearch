<?php

$logging_base_dir = elgg_get_data_path() . 'elasticsearch/';
$path = get_input('path');
$path = sanitise_filepath($path);

$logging_dir = $logging_base_dir . $path;

echo elgg_view('output/longtext', array('value' => elgg_echo('elasticsearch:logging:description')));

if (!empty($path)) {
	$parts = explode('/', trim($path, '/'));
	
	$urls = array();
	$urls[] = elgg_view('output/url', array(
		'text' => elgg_echo('elasticsearch:logging:root'),
		'href' => "admin/elasticsearch/logging",
		'is_trusted' => true,
	));
	
	$new_path = '';
	foreach ($parts as $part) {
		$new_path .= "{$part}/";
		
		$urls[] = elgg_view('output/url', array(
			'text' => $part,
			'href' => "admin/elasticsearch/logging?path={$new_path}",
			'is_trusted' => true,
		));
	}
	
	echo elgg_format_element('div', array('class' => 'mbs'), implode(' > ', $urls));
}


try {
	$flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS;
	$fs_it = new FilesystemIterator($logging_dir, $flags);
} catch (Exception $e) {
	echo elgg_echo('notfound');
	return;
}

$lis = array();

foreach ($fs_it as $file => $info) {
	
	$li = '';
	
	$basename = $info->getBasename();
	$path = str_ireplace($logging_base_dir, '', $file);
	
	if ($fs_it->isDir()) {
		$li .= elgg_view_icon('folder', array('class' => 'mrs'));
		$li .= elgg_view('output/url', array(
			'text' => $basename,
			'href' => "admin/elasticsearch/logging?path={$path}",
			'is_trusted' => true,
		));
	} else {
		elgg_load_css('lightbox');
		elgg_load_js('lightbox');
		
		$li .= elgg_view_icon('file', array('class' => 'mrs'));
		$li .= elgg_view('output/url', array(
			'text' => $basename,
			'href' => "ajax/view/elasticsearch/logging/view?path={$path}",
			'class' => 'elgg-lightbox',
			'is_trusted' => true,
			'data-colorbox-opts' => json_encode(array(
				'maxWidth' => '60%'
			)),
		));
	}
	
	$lis[] = $li;
}

if (!empty($lis)) {
	echo elgg_format_element('ul', array(), '<li>' . implode('</li><li>', $lis) . '</li>');
} else {
	echo elgg_echo('notfound');
}