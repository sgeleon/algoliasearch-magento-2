<?php

namespace Algolia\AlgoliaSearch\Setup\Patch\Data;

use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Algolia\AlgoliaSearch\Service\Product\ReplicaManager;
use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchInterface;
use Magento\Store\Model\StoreManagerInterface;

class RebuildReplicasPatch implements DataPatchInterface
{
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup,
        protected StoreManagerInterface    $storeManager,
        protected ReplicaManager           $replicaManager,
        protected ProductHelper            $productHelper,
        protected State                    $state
    )
    {}

        /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [
            MigrateVirtualReplicaConfigPatch::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply(): PatchInterface
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            $this->replicaManager->deleteReplicasFromAlgolia($storeId);
            $this->replicaManager->syncReplicasToAlgolia($storeId, $this->productHelper->getIndexSettings($storeId));
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }
}
