<?php

namespace Algolia\AlgoliaSearch\Api\Product;

use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Algolia\AlgoliaSearch\Exceptions\ExceededRetriesException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ReplicaManagerInterface
{
    public const SORT_ATTRIBUTE_PRICE = 'price';

    public const SORT_KEY_ATTRIBUTE_NAME = 'attribute';
    public const SORT_KEY_VIRTUAL_REPLICA = 'virtualReplica';
    public const MAX_VIRTUAL_REPLICA_LIMIT = 20;


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
    public function syncReplicasToAlgolia(string $primaryIndexName, int $storeId, array $primaryIndexSettings): void;


    /**
     * For standard Magento front end (e.g. Luma) replicas will likely only be needed if InstantSearch is enabled
     * Headless implementations may wish to override this behavior via plugin
     * @param int $storeId
     * @return bool
     */
    public function isReplicaSyncEnabled(int $storeId): bool;

    /**
     * Return the number of virtual replicas permitted per index
     * @link https://www.algolia.com/doc/guides/managing-results/refine-results/sorting/in-depth/replicas/#differences
     *
     * @return int
     */
    public function getMaxVirtualReplicasPerIndex() : int;
}
