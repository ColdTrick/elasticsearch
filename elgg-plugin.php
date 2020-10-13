<?php

use ColdTrick\ElasticSearch\Upgrades\RemoveLogs;

define('ELASTICSEARCH_INDEXED_NAME', 'elasticsearch_last_indexed');

require_once(__DIR__ . '/lib/functions.php');

return [
	'settings' => [
		'sync' => 'no',
		'search' => 'no',
		'search_score' => 'no',
		'cron_validate' => 'no',
		'ignore_ssl' => 0,
		'decay_time_field' => 'time_created',
	],
	'actions' => [
		'elasticsearch/admin_search' => [
			'access' => 'admin',
		],
		'elasticsearch/admin/index_management' => [
			'access' => 'admin',
		],
		'elasticsearch/admin/reindex' => [
			'access' => 'admin',
		],
		'elasticsearch/admin/download_log' => [
			'access' => 'admin',
		],
		'elasticsearch/admin/reindex_entity' => [
			'access' => 'admin',
		],
		'elasticsearch/admin/delete_entity' => [
			'access' => 'admin',
		],
	],
	'cli_commands' => [
		\ColdTrick\ElasticSearch\Cli\Sync::class,
	],
	'events' => [
		'ban' => [
			'user' => [
				'\ColdTrick\ElasticSearch\EventDispatcher::banUser' => [],
			],
		],
		'create' => [
			'all' => [
				'\ColdTrick\ElasticSearch\EventDispatcher::create' => [],
			],
		],
		'delete' => [
			'all' => [
				'\ColdTrick\ElasticSearch\EventDispatcher::delete' => [],
			],
		],
		'disable' => [
			'all' => [
				'\ColdTrick\ElasticSearch\EventDispatcher::disable' => [],
			],
		],
		'update' => [
			'all' => [
				'\ColdTrick\ElasticSearch\EventDispatcher::update' => [],
			],
		],
	],
	'hooks' => [
		'cron' => [
			'daily' => [
				'\ColdTrick\ElasticSearch\Cron::dailyCleanup' => [],
			],
			'minute' => [
				'\ColdTrick\ElasticSearch\Cron::minuteSync' => [],
			],
		],
		'export:counters' => [
			'elasticsearch' => [
				'\ColdTrick\ElasticSearch\Export::exportGroupMemberCount' => [],
				'\ColdTrick\ElasticSearch\Export::exportLikesCount' => [],
				'\ColdTrick\ElasticSearch\Export::exportCommentsCount' => [],
			],
		],
		'export:metadata_names' => [
			'elasticsearch' => [
				'\ColdTrick\ElasticSearch\Export::exportProfileMetadata' => [],
			],
		],
		'register' => [
			'menu:entity' => [
				'\ColdTrick\ElasticSearch\Menus\Entity::inspect' => [],
			],
			'menu:page' => [
				'\ColdTrick\ElasticSearch\Menus\Page::admin' => [],
			],
			'menu:search_list' => [
				'\ColdTrick\ElasticSearch\Menus\SearchList::registerSortMenu' => [],
			],
		],
		'search:params' => [
			'all' => [
				'\ColdTrick\ElasticSearch\SearchHooks::searchParams' => [],
			],
		],
		'search:fields' => [
			'all' => [
				'\ColdTrick\ElasticSearch\SearchHooks::searchFields' => [
					'priority' => 999,
				],
				'\ColdTrick\ElasticSearch\SearchHooks::searchFieldsNameToTitle' => [
					'priority' => 999,
				],
			],
			'group' => [
				'\ColdTrick\ElasticSearch\SearchHooks::groupSearchFields' => [],
			],
			'object' => [
				'\ColdTrick\ElasticSearch\SearchHooks::objectSearchFields' => [],
			],
			'user' => [
				'\ColdTrick\ElasticSearch\SearchHooks::userSearchFields' => [],
			],
		],
		'search:options' => [
			'all' => [
				'\ColdTrick\ElasticSearch\SearchHooks::searchOptions' => [],
			],
		],
		'search:results' => [
			'combined:all' => [
				'\ColdTrick\ElasticSearch\SearchHooks::searchEntities' => [],
			],
			'combined:objects' => [
				'\ColdTrick\ElasticSearch\SearchHooks::searchEntities' => [],
			],
			'entities' => [
				'\ColdTrick\ElasticSearch\SearchHooks::searchEntities' => [],
			],
		],
		'search_params' => [
			'elasticsearch' => [
				'\ColdTrick\ElasticSearch\SearchHooks::filterProfileFields' => [],
				'\ColdTrick\ElasticSearch\SearchHooks::sortByGroupMembersCount' => [],
			],
		],
		'to:entity' => [
			'elasticsearch' => [
				'\ColdTrick\ElasticSearch\SearchHooks::sourceToEntity' => [],
			],
		],
		'to:object' => [
			'entity' => [
				'\ColdTrick\ElasticSearch\Export::entityToObject' => [],
				'\ColdTrick\ElasticSearch\Export::entityRelationshipsToObject' => [],
				'\ColdTrick\ElasticSearch\Export::entityMetadataToObject' => [],
				'\ColdTrick\ElasticSearch\Export::entityCountersToObject' => [],
				'\ColdTrick\ElasticSearch\Export::profileTagFieldsToTags' => [],
				'\ColdTrick\ElasticSearch\Export::stripTags' => [
					'priority' => 9999,
				],
			],
		],
		'view_vars' => [
			'object/elements/imprint/contents' => [
				'\ColdTrick\ElasticSearch\Views::displaySearchScoreInImprint' => [],
			],
			'resources/livesearch/users' => [
				'\ColdTrick\ElasticSearch\Views::allowBannedUsers' => [
					'priority' => 600,
				],
			],
			'search/entity' => [
				'\ColdTrick\ElasticSearch\Views::preventSearchFieldChanges' => [],
			],
		],
	],
	'upgrades' => [
		RemoveLogs::class,
	],
	'view_extensions' => [
		'admin.css' => [
			'elasticsearch/admin.css' => [],
		],
	],
];
