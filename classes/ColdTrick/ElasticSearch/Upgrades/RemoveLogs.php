<?php

namespace ColdTrick\ElasticSearch\Upgrades;

use Elgg\Upgrade\AsynchronousUpgrade;
use Elgg\Upgrade\Result;

class RemoveLogs implements AsynchronousUpgrade {

	/**
	 * {@inheritDoc}
	 * @see \Elgg\Upgrade\Batch::getVersion()
	 */
	public function getVersion() {
		return '2019120300';
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Elgg\Upgrade\Batch::needsIncrementOffset()
	 */
	public function needsIncrementOffset() {
		return false;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Elgg\Upgrade\Batch::countItems()
	 */
	public function countItems() {
		return 1;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Elgg\Upgrade\Batch::run()
	 */
	public function run(Result $result, $offset) {
		
		if (elgg_delete_directory($this->getLogLocation())) {
			$result->addSuccesses();
		}
		
		return $result;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Elgg\Upgrade\Batch::shouldBeSkipped()
	 */
	public function shouldBeSkipped() {
		return !is_dir($this->getLogLocation());
	}
	
	/**
	 * Get logging location to be removed
	 *
	 * @return string
	 */
	protected function getLogLocation() {
		return elgg_get_data_path() . 'elasticsearch/';
	}
}
