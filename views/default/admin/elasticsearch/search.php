<?php

echo elgg_view_form('elasticsearch/admin_search');

echo elgg_view_module('inline', 'Search Results', 'Results will be shown here', ['id' => 'elasticsearch-admin-search-results']);