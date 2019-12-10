<?php

namespace ColdTrick\ElasticSearch\Di;

use \Elasticsearch\Common\Exceptions\ElasticsearchException;

class SearchService extends BaseClientService {

	/**
	 * {@inheritDoc}
	 */
	public static function name() {
		return 'elasticsearch.searchservice';
	}
	
	/**
	 * Inspect a GUID in Elasticsearch
	 *
	 * @param int  $guid       the GUID to inspect
	 * @param bool $return_raw return full return or only _source (default: false)
	 *
	 * @return false|array
	 */
	public function inspect(int $guid, bool $return_raw = false) {
		
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$result = $this->getClient()->get([
				'id' => $guid,
				'index' => $this->getIndex(),
			]);
			
			if ($return_raw) {
				return $result;
			}
			
			return elgg_extract('_source', $result);
		} catch (ElasticsearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
}
