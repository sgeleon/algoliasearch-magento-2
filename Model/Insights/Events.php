<?php

namespace Algolia\AlgoliaSearch\Model\Insights;

use Algolia\AlgoliaSearch\Api\Insights\EventsInterface;
use Algolia\AlgoliaSearch\Api\InsightsClient;
use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;

class Events implements EventsInterface
{
    public function __construct(
        protected ?InsightsClient        $client = null,
        protected ?string                $userToken = null,
        protected ?string                $authenticatedUserToken = null,
        protected ?StoreManagerInterface $storeManager = null
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

    public function setStoreManager(string $storeManager): EventsInterface
    {
        $this->storeManager = $storeManager;
        return $this;
    }

    /**
     * @return void
     * @throws AlgoliaException
     */
    private function checkDependencies(): void
    {
        if (
            !$this->client
            || !$this->userToken
            || !$this->storeManager
        ) {
            throw new AlgoliaException("Events model is missing necessary dependencies to function.");
        }
    }

    /**
     * @inheritDoc
     */
    public function convertedObjectIDsAfterSearch(string $eventName, string $indexName, array $objectIDs, string $queryID, array $requestOptions = []): array
    {
        $this->checkDependencies();

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
        $this->checkDependencies();
        return $this->converted(['objectIDs' => $objectIDs], $eventName, $indexName, $requestOptions);
    }

    private function converted($event, $eventName, $indexName, $requestOptions): array
    {
        $event = array_merge($event, [
            'eventType' => 'conversion',
            'eventName' => $eventName,
            'index'     => $indexName,
        ]);

        return $this->sendEvent($event, $requestOptions);
    }

    private function sendEvent($event, $requestOptions = []): array
    {
        $event['userToken'] = $this->userToken;
        if ($this->authenticatedUserToken) {
            $event['authenticatedUserToken'] = $this->authenticatedUserToken;
        }
        return $this->client->pushEvents([ 'events' => [$event] ], $requestOptions);
    }

    /**
     * @inheritDoc
     */
    public function convertAddToCart(string $eventName, string $indexName, Item $item, string $queryID = null): array
    {
        $this->checkDependencies();

        $event = [
            'eventSubtype' => self::CONVERSION_EVENT_SUBTYPE_CART,
            'objectIDs'    => [$item->getProduct()->getId()],
            'objectData'   => [[
                'price'    => $item->getPrice(),
//                'discount' => $item->getDiscountAmount(),
                'quantity' => (int) $item->getData('qty_to_add')
            ]],
            'currency'     => $this->storeManager->getStore()->getCurrentCurrency()->getCode()
        ];

        if ($queryID) {
            $event['queryID'] = $queryID;
        }

        return $this->converted($event, $eventName, $indexName, []);
    }
}
