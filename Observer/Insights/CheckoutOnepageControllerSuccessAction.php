<?php

namespace Algolia\AlgoliaSearch\Observer\Insights;

use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\InsightsHelper;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

class CheckoutOnepageControllerSuccessAction implements ObserverInterface
{
    /** @var string  */
    public const PLACE_ORDER_EVENT_NAME = 'Placed order';

    /** @var string  */
    protected const NO_QUERY_ID_KEY = '__NO_QUERY_ID__';

    /**
     * @param Data $dataHelper
     * @param InsightsHelper $insightsHelper
     * @param OrderFactory $orderFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Data $dataHelper,
        protected InsightsHelper $insightsHelper,
        protected OrderFactory $orderFactory,
        protected LoggerInterface $logger
    ) {}

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order) {
            $orderId = $observer->getEvent()->getOrderIds()[0];
            $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        }


        if (!$order || !$this->insightsHelper->isOrderPlacedTracked($order->getStoreId())) {
            return;
        }

        $indexName = "";
        try {
            $this->dataHelper->getIndexName('_products', $order->getStoreId()),
        } catch (NoSuchEntityException $e) {
            $this->logger->error("No store found for order: ", $e->getMessage());
            return;
        }

        $eventsModel = $this->insightsHelper->getEventsModel();
        $itemsByQueryId = $this->getItemsByQueryId($order);

        if (count($itemsByQueryId)) {
            foreach ($itemsByQueryId as $queryId => $items) {

                try {
                    $eventsModel->convertPurchase(
                        __(self::PLACE_ORDER_EVENT_NAME),
                        $indexName,
                        $items,
                        $queryId !== self::NO_QUERY_ID_KEY ? $queryId : null
                    );
                } catch (AlgoliaException $e) {
                    $this->logger->critical("Unable to send events due to Algolia events model misconfiguration: " . $e->getMessage());
                    break;
                } catch (LocalizedException $e) {
                    $this->logger->error("Failed sending event: " . $e->getMessage());
                    continue;
                }

            }
        }

    }

    /**
     * For a given Magento Order return an array of Items grouped by query ID.
     * If no query ID is found group this under self::NO_QUERY_ID_KEY
     * (while `null` could be used this may lead to unexpected behavior)
     * @param Order $order
     * @return array<string, array<Item[]>
     * NOTE: Items are not deduplicated so that aggregate revenue can be calculated by events model as needed
     */
    protected function getItemsByQueryId(Order $order): array
    {
        $itemsByQueryId = [];
        /** @var Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            if ($item->hasData(InsightsHelper::QUOTE_ITEM_QUERY_PARAM)) {
                $queryId = $item->getData(InsightsHelper::QUOTE_ITEM_QUERY_PARAM);
                $itemsByQueryId[$queryId][] = $item;
            }
            else {
                $itemsByQueryId[self::NO_QUERY_ID_KEY][] = $item;
            }
        }

        return $itemsByQueryId;
    }
}
