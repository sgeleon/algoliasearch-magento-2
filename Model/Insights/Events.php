<?php

namespace Algolia\AlgoliaSearch\Model\Insights;

use Algolia\AlgoliaSearch\Api\Insights\EventsInterface;
use Algolia\AlgoliaSearch\Api\InsightsClient;

class Events implements EventsInterface
{
    public function __construct(
        protected ?InsightsClient $client = null,
        protected ?string         $userToken = null,
        protected ?string         $authenticatedUserToken = null
    ) {}

    public function setInsightsClient(InsightsClient $client): EventsInterface
    {
        $this->insightsClient = $client;
        return $this;
    }

    public function setAuthenticatedUserToken(string $token): EventsInterface
    {
        $this->authenticatedUserToken = $token;
        return $this;
    }

    public function setAnonymousUserToken(string $token): EventsInterface
    {
        $this->userToken = $token;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function convertedObjectIDsAfterSearch(string $eventName, string $indexName, array $objectIDs, string $queryID, array $requestOptions = []): array
    {
        $event = [
            'objectIDs' => $objectIDs,
            'queryID'   => $queryID,
        ];

        return $this->converted($event, $eventName, $indexName, $requestOptions);
    }

    /**
     * @inheritDoc
     */
    public function convertedObjectIDs(string $eventName, string $indexName, array $objectIDs, array $requestOptions = []): array
    {
        return $this->converted(['objectIDs' => $objectIDs], $eventName, $indexName, $requestOptions);
    }

    private function converted($event, $eventName, $indexName, $requestOptions)
    {
        $event = array_merge($event, [
            'eventType' => 'conversion',
            'eventName' => $eventName,
            'index'     => $indexName,
        ]);

        return $this->sendEvent($event, $requestOptions);
    }

    private function sendEvent($event, $requestOptions = [])
    {
        $event['userToken'] = $this->userToken;
        return $this->client->pushEvents([ 'events' => [$event] ], $requestOptions);
    }
}
