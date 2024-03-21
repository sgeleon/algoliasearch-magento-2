<?php

namespace Algolia\AlgoliaSearch\Api\Insights;

use Algolia\AlgoliaSearch\Api\InsightsClient;

interface EventsInterface
{
    /** @var string  */
    public const CONVERSION_EVENT_SUBTYPE_CART = 'addToCart';
    /** @var string  */
    public const CONVERSION_EVENT_SUBTYPE_PURCHASE = 'purchase';

    public function setInsightsClient(InsightsClient $client): EventsInterface;

    public function setAuthenticatedUserToken(string $token): EventsInterface;

    public function setAnonymousUserToken(string $token): EventsInterface;

    /**
     * @param string $eventName
     * @param string $indexName
     * @param array $objectIDs
     * @param string $queryID
     * @param array $requestOptions
     * @return array<string, mixed>
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
     */
    public function convertedObjectIDs(
        string $eventName,
        string $indexName,
        array $objectIDs,
        array $requestOptions = []
    ): array;
}
