<?php

namespace Algolia\AlgoliaSearch\Api\Insights;

use Algolia\AlgoliaSearch\Api\InsightsClient;
use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item;

interface EventsInterface
{
    /** @var string */
    public const EVENT_KEY_SUBTYPE = 'eventSubtype';
    /** @var string */
    public const EVENT_KEY_OBJECT_IDS = 'objectIDs';
    /** @var string */
    public const EVENT_KEY_OBJECT_DATA = 'objectData';
    /** @var string */
    public const EVENT_KEY_CURRENCY = 'currency';
    /** @var string */
    public const EVENT_KEY_VALUE = 'value';
    /** @var string */
    public const EVENT_KEY_QUERY_ID = 'queryID';

    /** @var string  */
    public const EVENT_SUBTYPE_CART = 'addToCart';
    /** @var string  */
    public const EVENT_SUBTYPE_PURCHASE = 'purchase';

    // https://www.algolia.com/doc/rest-api/insights/#method-param-objectids
    /** @var int  */
    public const MAX_OBJECT_IDS_PER_EVENT = 20;

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
     * @return array<string, mixed> API response
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
     * @return array<string, mixed> API response
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
     * @param string|null $queryID API response
     * @return array<string, mixed>
     * @throws AlgoliaException
     * @throws LocalizedException
     */
    public function convertAddToCart(
        string $eventName,
        string $indexName,
        Item $item,
        string $queryID = null
    ): array;

    /**
     * @param string $eventName
     * @param string $indexName
     * @param Item[] $items
     * @param string|null $queryID
     * @return array<string, mixed> API response
     * @throws AlgoliaException
     * @throws LocalizedException
     */
    public function convertPurchase(
        string $eventName,
        string $indexName,
        array $items,
        string $queryID = null
    ): array;
}
