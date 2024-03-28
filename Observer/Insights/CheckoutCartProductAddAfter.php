<?php

namespace Algolia\AlgoliaSearch\Observer\Insights;

use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Configuration\PersonalizationHelper;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\InsightsHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;

class CheckoutCartProductAddAfter implements ObserverInterface
{
    protected ConfigHelper $configHelper;
    protected PersonalizationHelper $personalizationHelper;

    /**
     * @param Data $dataHelper
     * @param InsightsHelper $insightsHelper
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Data $dataHelper,
        protected InsightsHelper $insightsHelper,
        protected RequestInterface $request,
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
     * ['quote_item' => $quoteItem, 'product' => $product]
     */
    public function execute(Observer $observer): void
    {
        /** @var Item $quoteItem */
        $quoteItem = $observer->getEvent()->getData('quote_item');
        /** @var Product $product */
        $product = $observer->getEvent()->getData('product');
        $storeId = $quoteItem->getStoreId();

        $isAddToCartTracked = $this->insightsHelper->isAddedToCartTracked($storeId);
        $isOrderPlacedTracked = $this->insightsHelper->isOrderPlacedTracked($storeId);

        if (!$isAddToCartTracked
            && !$isOrderPlacedTracked
            || !$this->insightsHelper->getUserAllowedSavedCookie()) {
            return;
        }

        $eventsModel = $this->insightsHelper->getEventsModel();

        $queryId = $this->request->getParam('queryID');

        /** Adding algolia_query_param to the items to track the conversion when product is added to the cart */
        if ($isOrderPlacedTracked && $queryId) {
            $this->addQueryIdToQuoteItems($product, $quoteItem, $queryId);
        }

        if ($isAddToCartTracked) {
            try {
                $eventsModel->convertAddToCart(
                    __('Added to Cart'),
                    $this->dataHelper->getIndexName('_products', $storeId),
                    $quoteItem,
                    $queryId
                );
            } catch (AlgoliaException $e) {
                $this->logger->critical("Unable to send add to cart event due to Algolia events model misconfiguration: " . $e->getMessage());
            } catch (LocalizedException $e) {
                $this->logger->error("Error tracking conversion for add to cart event: " . $e->getMessage());
            }
        }
    }
}
