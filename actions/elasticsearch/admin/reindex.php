<?php

elgg_set_plugin_setting('reindex_ts', time(), 'elasticsearch');

return elgg_ok_response();
