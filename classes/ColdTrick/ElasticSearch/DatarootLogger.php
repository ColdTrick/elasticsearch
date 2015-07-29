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
		$handler = new RotatingFileHandler(
			elgg_get_data_path() . 'elasticsearch/client.log',
			0,
			_elgg_services()->logger->getLevel()
		);
		var_dump(_elgg_services()->logger->getLevel());
		
		$handler->setFilenameFormat('{date}_{filename}', 'Y/m/d');
		
		// set logging processor
		$processor = new IntrospectionProcessor();
		$this->pushProcessor($processor);
	}
}
