<?php

namespace Algolia\AlgoliaSearch\Model\Source;

use Algolia\AlgoliaSearch\Helper\InsightsHelper;
use Magento\Framework\Data\OptionSourceInterface;

class ConversionAnalyticsMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => InsightsHelper::CONVERSION_ANALYTICS_MODE_DISABLE, 'label' => __('[Disabled]')],
            ['value' => InsightsHelper::CONVERSION_ANALYTICS_MODE_CART, 'label' => __('Track "Add to cart" action as conversion')],
            ['value' => InsightsHelper::CONVERSION_ANALYTICS_MODE_PURCHASE, 'label' => __('Track "Place Order" action as conversion')],
        ];
    }
}
