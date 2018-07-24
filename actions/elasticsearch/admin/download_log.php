<?php

use Elgg\Project\Paths;

$path = get_input('path');
$path = Paths::sanitize($path, false);
if (empty($path)) {
	return elgg_error_response(elgg_echo('error:missing_data'));
}

$logging_base_dir = elgg_get_data_path() . 'elasticsearch/';

// check if the requested file exists
$filename = $logging_base_dir . $path;
if (!file_exists($filename)) {
	return elgg_error_response(elgg_echo('error:404:content'));
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
