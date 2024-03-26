<?php

namespace Algolia\AlgoliaSearch\Helper\Configuration;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigChecker
{
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected StoreManagerInterface $storeManager
    ) {}

    public function isSettingAppliedForScopeAndCode(string $path, string $scope, string $code): bool
    {
        $value = $this->scopeConfig->getValue($path, $scope, $code);
        $defaultValue = $this->scopeConfig->getValue($path);
        return ($value !== $defaultValue);
    }

    /**
     * For a given path, check if that path has a non-default value
     * and if so perform corresponding logic (specified via callback)
     *
     * @param string $path
     * @param callable $callback Callback to execute for a given config scope
     *                           Signature: function(string $scope, string $scopeCode = null)
     * @param bool $includeDefault Update the default (global) scope as well (defaults to true)
     *
     * @return void
     */
    public function checkAndApplyAllScopes(string $path, callable $callback, bool $includeDefault = true) {
        // First update all the possible scoped configurations
        /** @var \Magento\Store\Api\Data\WebsiteInterface $website */
        foreach ($this->storeManager->getWebsites() as $website) {
            if ($this->isSettingAppliedForScopeAndCode(
                ConfigHelper::CC_CONVERSION_ANALYTICS_MODE,
                ScopeInterface::SCOPE_WEBSITES,
                $website->getCode()
            )) {
                $callback(ScopeInterface::SCOPE_WEBSITES, $website->getCode());
            }
        }

        /** @var \Magento\Store\Api\Data\StoreInterface $store */
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->isSettingAppliedForScopeAndCode(
                ConfigHelper::CC_CONVERSION_ANALYTICS_MODE,
                ScopeInterface::SCOPE_STORES,
                $store->getCode()
            )) {
                $callback(ScopeInterface::SCOPE_STORES, $store->getCode());
            }
        }

        // Update the default configuration *last* so that initial scope comparisons work as expected
        if ($includeDefault) {
            $callback(ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        }
    }
}
