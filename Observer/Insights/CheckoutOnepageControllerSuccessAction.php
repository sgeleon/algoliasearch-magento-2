<?php

namespace Algolia\AlgoliaSearch\Observer\Insights;

use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\InsightsHelper;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

class CheckoutOnepageControllerSuccessAction implements ObserverInterface
{
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

        $eventsModel = $this->insightsHelper->getEventsModel();
        $orderItems = $order->getAllVisibleItems();

        if ($this->insightsHelper->isOrderPlacedTracked($order->getStoreId())) {
            $queryIds = [];
            /** @var Item $item */
            foreach ($orderItems as $item) {
                if ($item->hasData(InsightsHelper::QUOTE_ITEM_QUERY_PARAM)) {
                    $queryId = $item->getData(InsightsHelper::QUOTE_ITEM_QUERY_PARAM);
                    $queryIds[$queryId][] = $item->getProductId();
                }
            }

            if (count($queryIds) > 0) {
                foreach ($queryIds as $queryId => $productIds) {

                    // Event can't process more than 20 objects
                    $productIds = array_slice($productIds, 0, 20);

                    try {
                        $eventsModel->convertedObjectIDsAfterSearch(
                            __('Placed Order'),
                            $this->dataHelper->getIndexName('_products', $order->getStoreId()),
                            array_unique($productIds),
                            $queryId
                        );
                    } catch (AlgoliaException $e) {
                        $this->logger->critical("Algolia events model misconfiguration: " . $e->getMessage());
                        continue;
                    } catch (NoSuchEntityException $e) {
                        $this->logger->error("No store found for order: ", $e->getMessage());
                        continue;
                    }

                }
            }
        } else {
            $productIds = [];
            /** @var Item $item */
            foreach ($orderItems as $item) {
                $productIds[] = $item->getProductId();

                // Event can't process more than 20 objects
                if (count($productIds) > 20) {
                    break;
                }
            }

            try {
                $eventsModel->convertedObjectIDs(
                    __('Placed Order'),
                    $this->dataHelper->getIndexName('_products', $order->getStoreId()),
                    array_unique($productIds)
                );
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage());
            }
        }

        return $this;
    }
}
