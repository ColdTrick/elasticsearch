<?php

namespace ColdTrick\ElasticSearch;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\IntrospectionProcessor;

/**
 * Custom Logger class that writes to dataroot
 *
 */
class DatarootLogger extends \Monolog\Logger {
	
	/**
	 * @param string             $name       The logging channel
	 * @param HandlerInterface[] $handlers   Optional stack of handlers, the first one in the array is called first, etc.
	 * @param callable[]         $processors Optional array of processors
	 */
	public function __construct($name, array $handlers = array(), array $processors = array()) {
		
		parent::__construct($name, $handlers, $processors);
		
		// set handler
		$elgg_log_level = _elgg_services()->logger->getLevel();
		if ($elgg_log_level == \Elgg\Logger::OFF) {
			// always log errors
			$elgg_log_level = \Elgg\Logger::ERROR;
		}
		
		$handler = new RotatingFileHandler(
			elgg_get_data_path() . 'elasticsearch/client.log',
			0,
			$elgg_log_level
		);
		
		// create correct folder structure
		$date = date('Y/m/');
		mkdir(elgg_get_data_path() . "elasticsearch/{$date}", 0755, true);
		$handler->setFilenameFormat('{date}_{filename}', 'Y/m/d');
		
		$this->pushHandler($handler);
		
		// set logging processor
		$processor = new IntrospectionProcessor();
		$this->pushProcessor($processor);
	}
}
