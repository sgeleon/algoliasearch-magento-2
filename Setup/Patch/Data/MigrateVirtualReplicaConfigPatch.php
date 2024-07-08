<?php

namespace Algolia\AlgoliaSearch\Setup\Patch\Data;

use Algolia\AlgoliaSearch\Api\Product\ReplicaManagerInterface;
use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Configuration\ConfigChecker;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchInterface;

class MigrateVirtualReplicaConfigPatch implements DataPatchInterface
{
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup,
        protected WriterInterface          $configWriter,
        protected ConfigInterface          $config,
        protected ScopeConfigInterface     $scopeConfig,
        protected ConfigChecker            $configChecker,
        protected ReplicaManagerInterface  $replicaManager
    )
    {}

    /**
     * @inheritDoc
     */
    public function apply(): PatchInterface
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->configChecker->checkAndApplyAllScopes(ConfigHelper::USE_VIRTUAL_REPLICA_ENABLED, [$this, 'migrateSetting']);

        // rebuild everything after

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public function migrateSetting(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, int $scopeId = 0): void
    {
        $value = (bool) $this->scopeConfig->getValue(ConfigHelper::USE_VIRTUAL_REPLICA_ENABLED, $scope, $scopeId);
        // not enabled moving on...
        if (!$value) {
            return;
        }

        // TODO...
        // retrieve the sorting config
        // turn on virtual replicas for all attributes
        // run the validator, throw ReplicaExceedsException if needed

        //if all is copacetic then save the new sorting config
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
