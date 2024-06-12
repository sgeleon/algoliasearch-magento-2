<?php

namespace Algolia\AlgoliaSearch\Api\Product;

use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Algolia\AlgoliaSearch\Exceptions\ExceededRetriesException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ReplicaManagerInterface
{
    /**
     * Configure replicas in Algolia based on the sorting configuration in Magento
     *
     * @param string $indexName Could be tmp (legacy impl)
     * @param int $storeId
     * @param array<string, mixed> $primaryIndexSettings
     * @return void
     *
     * @throws AlgoliaException
     * @throws ExceededRetriesException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function handleReplicas(string $indexName, int $storeId, array $primaryIndexSettings): void;
}
