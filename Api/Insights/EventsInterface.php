<?php

namespace Algolia\AlgoliaSearch\Api\Insights;

use Algolia\AlgoliaSearch\Api\InsightsClient;

interface EventsInterface
{
    public function setInsightsClient(InsightsClient $client): EventsInterface;

    public function setAuthenticatedUserToken(string $token): EventsInterface;

    public function setAnonymousUserToken(string $token): EventsInterface;
}
