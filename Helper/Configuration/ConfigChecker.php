<?php

namespace Algolia\AlgoliaSearch\Helper\Configuration;

use Magento\Framework\App\Config\ScopeConfigInterface;
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

}
