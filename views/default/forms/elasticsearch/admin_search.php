<?php

elgg_require_js('elasticsearch/admin_search');

echo elgg_view('input/plaintext', [
	'name' => 'q',
	'placeholder' => elgg_echo('elasticsearch:forms:admin_search:query:placeholder'),
]);

echo elgg_view('input/submit', ['value' => elgg_echo('search')]);