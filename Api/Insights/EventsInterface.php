<?php

namespace Algolia\AlgoliaSearch\Api\Insights;

use Algolia\AlgoliaSearch\Api\InsightsClient;
use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Magento\Quote\Model\Quote\Item;

interface EventsInterface
{
    /** @var string  */
    public const CONVERSION_EVENT_SUBTYPE_CART = 'addToCart';
    /** @var string  */
    public const CONVERSION_EVENT_SUBTYPE_PURCHASE = 'purchase';

    public function setInsightsClient(InsightsClient $client): EventsInterface;

    public function setAuthenticatedUserToken(string $token): EventsInterface;

    public function setAnonymousUserToken(string $token): EventsInterface;

    public function setStoreManager(string $storeManager): EventsInterface;

    /**
     * @param string $eventName
     * @param string $indexName
     * @param array $objectIDs
     * @param string $queryID
     * @param array $requestOptions
     * @return array<string, mixed>
     * @throws AlgoliaException
     */
    public function convertedObjectIDsAfterSearch(
        string $eventName,
        string $indexName,
        array $objectIDs,
        string $queryID,
        array $requestOptions = []
    ): array;

    /**
     * @param string $eventName
     * @param string $indexName
     * @param array $objectIDs
     * @param array $requestOptions
     * @return array<string, mixed>
     * @throws AlgoliaException
     */
    public function convertedObjectIDs(
        string $eventName,
        string $indexName,
        array $objectIDs,
        array $requestOptions = []
    ): array;

    /**
     * @param string $eventName
     * @param string $indexName
     * @param Item $item
     * @param string|null $queryID
     * @return array<string, mixed>
     * @throws AlgoliaException
     */
    public function convertAddToCart(
        string $eventName,
        string $indexName,
        Item $item,
        string $queryID = null
    ): array;
}
