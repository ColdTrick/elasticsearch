<?php

$index = get_input('index');
$task = get_input('task');
if (empty($index)) {
	register_error(elgg_echo('elasticsearch:error:no_index'));
	forward(REFERER);
}

$client = elasticsearch_get_client();
if (empty($client)) {
	register_error(elgg_echo('elasticsearch:error:no_client'));
	forward(REFERER);
}

$exists = false;
try {
	$exists = $client->indices()->exists(array('index' => $index));
} catch (Exception $e) {
	// something is wrong
}

switch ($task) {
	case 'flush':
		
		if (!$exists) {
			register_error(elgg_echo('elasticsearch:error:index_not_exists', array($index)));
			break;
		}
		
		try {
			$client->indices()->flush(array('index' => $index));
		} catch (Exception $e) {
			register_error(elgg_echo('elasticsearch:action:admin:index_management:error:flush', array($index)));
			break;
		}
		
		system_message(elgg_echo('elasticsearch:action:admin:index_management:flush', array($index)));
		
		break;
	case 'optimize':
		
		if (!$exists) {
			register_error(elgg_echo('elasticsearch:error:index_not_exists', array($index)));
			break;
		}
		
		try {
			$client->indices()->optimize(array(
				'index' => $index,
				'max_num_segments' => 1,
				'wait_for_merge' => false
			));
		} catch (Exception $e) {
			register_error(elgg_echo('elasticsearch:action:admin:index_management:error:optimize', array($index)));
			break;
		}
		
		system_message(elgg_echo('elasticsearch:action:admin:index_management:optimize', array($index)));
		
		break;
	case 'delete':
		
		if (!$exists) {
			register_error(elgg_echo('elasticsearch:error:index_not_exists', array($index)));
			break;
		}
		
		try {
			$client->indices()->delete(array('index' => $index));
		} catch (Exception $e) {
			register_error(elgg_echo('elasticsearch:action:admin:index_management:error:delete', array($index)));
			break;
		}
		
		system_message(elgg_echo('elasticsearch:action:admin:index_management:delete', array($index)));
		
		break;
	case 'create':
		
		if ($exists) {
			register_error(elgg_echo('elasticsearch:action:admin:index_management:error:create:exists'));
			break;
		}
		
		try {
			$client->indices()->create(array('index' => $index));
		} catch (Exception $e) {
			register_error(elgg_echo('elasticsearch:action:admin:index_management:error:create', array($index)));
			break;
		}
		
		system_message(elgg_echo('elasticsearch:action:admin:index_management:create', array($index)));
		
		break;
	default:
		register_error(elgg_echo('elasticsearch:action:admin:index_management:error:task', array($task)));
		break;
}

forward(REFERER);
