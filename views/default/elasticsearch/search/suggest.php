<?php

$client = elasticsearch_get_client();
if (!$client) {
	return;
}

$query = get_input('q');
if (empty($query)) {
	return;
}

$suggestions = $client->getSuggestions();
if (empty($suggestions)) {
	return;
}

$suggestions = $suggestions['suggestions'][0]['options'];
if (empty($suggestions)) {
	return;
}

$suggestion = $suggestions[0]['text'];

$url = elgg_view('output/url', [
	'text' => $suggestion,
	'href' => elgg_http_add_url_query_elements(current_page_url(), ['q' => $suggestion]),
	'class' => 'elasticsearch-suggest-link',
]);

$query = elgg_format_element('span', ['class' => 'elasticsearch-suggest-original-query'], $query);

echo elgg_echo('elasticsearch:suggest', [$url, $query]);
