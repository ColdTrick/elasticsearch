<?php

elgg_set_plugin_setting('reindex_ts', time(), 'elasticsearch');

forward(REFERER);