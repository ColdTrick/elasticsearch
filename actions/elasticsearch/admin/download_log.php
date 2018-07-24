<?php

$path = get_input('path');
$path = sanitise_filepath($path, false);
if (empty($path)) {
	register_error(elgg_echo('error:missing_data'));
	forward(REFERER);
}

$logging_base_dir = elgg_get_data_path() . 'elasticsearch/';

// check if the requested file exists
$filename = $logging_base_dir . $path;
if (!file_exists($filename)) {
	register_error(elgg_echo('error:404:content'));
	forward(REFERER);
}

// get contents
$contents = file_get_contents($filename);

// begin download
header('Pragma: public');
header('Content-Type: text/plain');
header('Content-Disposition: Attachment; filename=' . basename($filename));
header('Content-Length: ' . strlen($contents));

echo $contents;
exit();
