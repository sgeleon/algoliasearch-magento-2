<?php

namespace Algolia\AlgoliaSearch\Model\Insights;

use Algolia\AlgoliaSearch\Api\Insights\EventsInterface;
use Algolia\AlgoliaSearch\Api\InsightsClient;
use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;

class Events implements EventsInterface
{
    /** @var array<int, float> */
    protected array $revenueByOrder = [];

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
     * @return string
     * @throws LocalizedException if unable to find store or currency
     */
    private function getCurrentCurrency(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * @inheritDoc
     */
    public function convertAddToCart(string $eventName, string $indexName, Item $item, string $queryID = null): array
    {
        $this->checkDependencies();

        $event = [
            self::EVENT_KEY_SUBTYPE     => self::EVENT_SUBTYPE_CART,
            self::EVENT_KEY_OBJECT_IDS  => [$item->getProduct()->getId()],
            self::EVENT_KEY_OBJECT_DATA => [[
                'price'    => $item->getPrice(),
                //'discount' => $item->getDiscountAmount(),
                'quantity' => (int) $item->getData('qty_to_add')
            ]],
            self::EVENT_KEY_CURRENCY    => $this->getCurrentCurrency()
        ];

        if ($queryID) {
            $event[self::EVENT_KEY_QUERY_ID] = $queryID;
        }

        return $this->converted($event, $eventName, $indexName, []);
    }

    /**
     * @inheritDoc
     */
    public function convertPurchase(string $eventName, string $indexName, array $items, string $queryID = null): array
    {
        $this->checkDependencies();

        $objectData = $this->getObjectDataForPurchase($items);

        $event = [
            self::EVENT_KEY_SUBTYPE     => self::EVENT_SUBTYPE_PURCHASE,
            self::EVENT_KEY_OBJECT_IDS  => $this->restrictMaxObjectsPerEvent($this->getObjectIdsForPurchase($items)),
            self::EVENT_KEY_OBJECT_DATA => $this->restrictMaxObjectsPerEvent($objectData),
            self::EVENT_KEY_CURRENCY    => $this->getCurrentCurrency(),
            self::EVENT_KEY_VALUE       => $this->getTotalRevenueForEvent($objectData)
        ];

        if ($queryID) {
            $event[self::EVENT_KEY_QUERY_ID] = $queryID;
        }

        return $this->converted($event, $eventName, $indexName, []);
    }

    protected function restrictMaxObjectsPerEvent(array $items): array
    {
        return array_slice($items, 0, self::MAX_OBJECT_IDS_PER_EVENT);
    }

    /**
     * Call this before enforcing the object limit (MAX_OBJECT_IDS_PER_EVENT)
     * to ensure full revenue capture in the value argument.
     *
     * @param array<array<string, mixed>> $objectData
     * @return float Total revenue
     */
    protected function getTotalRevenueForEvent(array $objectData): float
    {
        return array_reduce($objectData, function($carry, $item) {
           return floatval($carry) + floatval($item['quantity']) * floatval($item['price']);
        });
    }

    /**
     * Extract Item into event object data.
     * Note that we must preserve redundancies because Magenot indexes at the parent configurable level
     * and different prices can result on variants for the same Algolia `objectID`
     *
     * @param Item[] $items
     * @return array<array<string, mixed>>
     */
    protected function getObjectDataForPurchase(array $items): array
    {
        return array_map(function($item) {
            return [
                'price' => $item->getPrice(),
                'quantity' => $item->getQty(),
                // 'discount' => 0
            ];
        }, $items);
    }

    /**
     * @param Item[] $items
     * @return int[]
     */
    protected function getObjectIdsForPurchase(array $items): array
    {
        return array_map(function($item) {
            return $item->getProduct()->getId();
        }, $items);
    }
}
