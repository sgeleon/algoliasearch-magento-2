<?php

namespace Algolia\AlgoliaSearch\Observer\Insights;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Configuration\PersonalizationHelper;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\InsightsHelper;
use Exception;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;

class CheckoutCartProductAddAfter implements ObserverInterface
{
    protected ConfigHelper $configHelper;
    protected PersonalizationHelper $personalizationHelper;

    /**
     * @param Data $dataHelper
     * @param InsightsHelper $insightsHelper
     * @param SessionManagerInterface $coreSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Data $dataHelper,
        protected InsightsHelper $insightsHelper,
        protected SessionManagerInterface $coreSession,
        protected LoggerInterface $logger
    ) {
        $this->configHelper = $this->insightsHelper->getConfigHelper();
        $this->personalizationHelper = $this->insightsHelper->getPersonalizationHelper();
    }

    protected function addQueryIdToQuoteItems(Product $product, Item $quoteItem, string $queryId): void
    {
        if ($product->getTypeId() == "grouped") {
            $groupProducts = $product->getTypeInstance()->getAssociatedProducts($product);
            foreach ($quoteItem->getQuote()->getAllItems() as $item) {
                foreach ($groupProducts as $groupProduct) {
                    if ($groupProduct->getId() == $item->getProductId()) {
                        $item->setData(InsightsHelper::QUOTE_ITEM_QUERY_PARAM, $queryId);
                    }
                }
            }
        } else {
            $quoteItem->setData(InsightsHelper::QUOTE_ITEM_QUERY_PARAM, $queryId);
        }
    }

    /**
     * @param Observer $observer
     * ['quote_item' => $result, 'product' => $product]
     */
    public function execute(Observer $observer)
    {
        /** @var Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        $storeId = $quoteItem->getStoreId();

        if (!$this->insightsHelper->isAddedToCartTracked($storeId)
            && !$this->insightsHelper->isOrderPlacedTracked($storeId)
            || !$this->insightsHelper->getUserAllowedSavedCookie()) {
            return;
        }

        $eventsModel = $this->insightsHelper->getEventsModel();

        $queryId = $this->coreSession->getQueryId();

        /** Adding algolia_query_param to the items to track the conversion when product is added to the cart */
        if ($this->insightsHelper->isOrderPlacedTracked($storeId) && $queryId) {
            $this->addQueryIdToQuoteItems($product, $quoteItem, $queryId);
        }

        if ($this->insightsHelper->isAddedToCartTracked($storeId)) {
            if ($queryId) {
                try {
                    $eventsModel->convertedObjectIDsAfterSearch(
                        __('Added to Cart'),
                        $this->dataHelper->getIndexName('_products', $storeId),
                        [$product->getId()],
                        $queryId
                    );
                } catch (Exception $e) {
                    $this->logger->critical($e);
                }
            }
            else {
                try {
                    $eventsModel->convertedObjectIDs(
                        __('Added to Cart'),
                        $this->dataHelper->getIndexName('_products', $storeId),
                        [$product->getId()]
                    );
                } catch (Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
    }
}
