<?php

namespace Algolia\AlgoliaSearch\Setup\Patch\Data;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Configuration\ConfigChecker;
use Algolia\AlgoliaSearch\Helper\InsightsHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class MigrateConversionAnalyticsModePatch implements DataPatchInterface, PatchRevertableInterface
{
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup,
        protected WriterInterface $configWriter,
        protected ConfigInterface $config,
        protected ScopeConfigInterface $scopeConfig,
        protected ConfigChecker $configChecker,
        protected StoreManagerInterface $storeManager
    ) {}

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        // First update all the possible subconfigurations
        /** @var \Magento\Store\Api\Data\WebsiteInterface $website */
        foreach ($this->storeManager->getWebsites() as $website) {
            if ($this->configChecker->isSettingAppliedForScopeAndCode(
                ConfigHelper::CC_CONVERSION_ANALYTICS_MODE,
                ScopeInterface::SCOPE_WEBSITES,
                $website->getCode()
            )) {
                $this->migrateSetting(ScopeInterface::SCOPE_WEBSITES, $website->getCode());
            }
        }

        /** @var \Magento\Store\Api\Data\StoreInterface $store */
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->configChecker->isSettingAppliedForScopeAndCode(
                ConfigHelper::CC_CONVERSION_ANALYTICS_MODE,
                ScopeInterface::SCOPE_STORES,
                $store->getCode()
            )) {
                $this->migrateSetting(ScopeInterface::SCOPE_STORES, $store->getCode());
            }
        }

        // Update the default configuration last so that initial "setting applied" comparisons work as expected
        $this->migrateSetting();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function migrateSetting(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, string $scopeCode = null): void
    {
        $value = $this->scopeConfig->getValue(ConfigHelper::CC_CONVERSION_ANALYTICS_MODE, $scope);
        if (in_array(
            $value,
            [
                InsightsHelper::CONVERSION_ANALYTICS_MODE_PURCHASE,
                InsightsHelper::CONVERSION_ANALYTICS_MODE_ALL
            ]
        )) {
           $this->configWriter->save(
               ConfigHelper::CC_CONVERSION_ANALYTICS_MODE,
               InsightsHelper::CONVERSION_ANALYTICS_MODE_ALL,
               $scope);
        }
    }

    /**
     * @inheritDoc
     */
    public function revert()
    {
        // TODO: Implement revert() method.
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
