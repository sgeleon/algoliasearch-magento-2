<?php

namespace Algolia\AlgoliaSearch\Model\Insights;

use Algolia\AlgoliaSearch\Api\Insights\EventsInterface;
use Algolia\AlgoliaSearch\Api\InsightsClient;

class Events implements EventsInterface
{
    public function __construct(
        protected ?InsightsClient $insightsClient          = null,
        protected ?string          $userToken              = null,
        protected ?string          $authenticatedUserToken = null
    ) { }

    public function setInsightsClient(InsightsClient $client) : EventsInterface
    {
        $this->insightsClient = $client;
        return $this;
    }

    public function setAuthenticatedUserToken(string $token) : EventsInterface
    {
        $this->authenticatedUserToken = $token;
        return $this;
    }

    public function setAnonymousUserToken(string $token) : EventsInterface
    {
        $this->userToken = $token;
        return $this;
    }
}
