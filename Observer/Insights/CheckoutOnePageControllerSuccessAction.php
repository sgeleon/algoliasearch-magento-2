<?php

namespace Algolia\AlgoliaSearch\Observer\Insights;

use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\InsightsHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

class CheckoutOnePageControllerSuccessAction implements ObserverInterface
{
    /** @var string  */
    public const PLACE_ORDER_EVENT_NAME = 'Placed order';

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
            $this->dataHelper->getIndexName('_products', $order->getStoreId());
        } catch (NoSuchEntityException $e) {
            $this->logger->error("No store found for order: " . $e->getMessage());
            return;
        }

        $eventsModel = $this->insightsHelper->getEventsModel();

        try {
            $eventsModel->convertPurchase(
                __(self::PLACE_ORDER_EVENT_NAME),
                $indexName,
                $order
            );
        } catch (AlgoliaException $e) {
            $this->logger->critical("Unable to send purchase events due to Algolia events model misconfiguration: " . $e->getMessage());
        } catch (LocalizedException $e) {
            $this->logger->error("Failed sending purchase events: " . $e->getMessage());
        }
    }

}
