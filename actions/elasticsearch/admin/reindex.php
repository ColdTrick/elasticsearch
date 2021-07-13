<?php

$plugin = elgg_get_plugin_from_id('elasticsearch');
$plugin->setSetting('reindex_ts', time());

return elgg_ok_response();
