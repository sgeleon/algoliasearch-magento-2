<?php

namespace Algolia\AlgoliaSearch\Observer\Insights;

use Algolia\AlgoliaSearch\Helper\Configuration\PersonalizationHelper;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\InsightsHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Wishlist\Model\Item;
use Psr\Log\LoggerInterface;

class WishlistProductAddAfter implements ObserverInterface
{

    /**
     * CheckoutCartProductAddAfter constructor.
     *
     * @param Data $dataHelper
     * @param PersonalizationHelper $personalisationHelper
     * @param InsightsHelper $insightsHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Data                  $dataHelper,
        protected PersonalizationHelper $personalisationHelper,
        protected InsightsHelper        $insightsHelper,
        protected LoggerInterface       $logger
    ) {}

    /**
     * @param Observer $observer
     * ['items' => $items]
     */
    public function execute(Observer $observer): void
    {
        $items = $observer->getEvent()->getItems();
        /** @var Item $firstItem */
        $firstItem = $items[0];

        if (!$this->personalisationHelper->isPersoEnabled($firstItem->getStoreId())
            || !$this->personalisationHelper->isWishlistAddTracked($firstItem->getStoreId())) {
            return;
        }

        $eventsModel = $this->insightsHelper->getEventsModel();
        $productIds = array_map(function (Item $item) {
            return $item->getProductId();
        }, $items);

        try {
            $eventsModel->convertedObjectIDs(
                __('Added to Wishlist'),
                $this->dataHelper->getIndexName('_products', $firstItem->getStoreId()),
                $productIds
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
