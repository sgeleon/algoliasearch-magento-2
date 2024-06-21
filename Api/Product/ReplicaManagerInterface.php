<?php

namespace Algolia\AlgoliaSearch\Api\Product;

use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Algolia\AlgoliaSearch\Exceptions\ExceededRetriesException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ReplicaManagerInterface
{
    public const REPLICA_TRANSFORM_MODE_STANDARD = 1;
    public const REPLICA_TRANSFORM_MODE_VIRTUAL = 2;
    public const REPLICA_TRANSFORM_MODE_ACTUAL = 3;

    public const SORT_KEY_VIRTUAL_REPLICA = 'virtualReplica';

    /**
     * Configure replicas in Algolia based on the sorting configuration in Magento
     *
     * @param string $primaryIndexName Could be tmp (legacy impl)
     * @param int $storeId
     * @param array<string, mixed> $primaryIndexSettings
     * @return void
     *
     * @throws AlgoliaException
     * @throws ExceededRetriesException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function handleReplicas(string $primaryIndexName, int $storeId, array $primaryIndexSettings): void;
}
