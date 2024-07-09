<?php

namespace Algolia\AlgoliaSearch\Setup\Patch\Data;

use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Algolia\AlgoliaSearch\Registry\ReplicaState;
use Algolia\AlgoliaSearch\Service\Product\ReplicaManager;
use Magento\Framework\App\State as AppState;
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
        protected AppState                 $appState,
        protected ReplicaState             $replicaState
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
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

        $storeIds = array_keys($this->storeManager->getStores());
        // Delete all replicas before resyncing in case of incorrect replica assignments
        foreach ($storeIds as $storeId) {
            $this->replicaManager->deleteReplicasFromAlgolia($storeId);
        }

        foreach ($storeIds as $storeId) {
            $this->replicaState->setChangeState(ReplicaState::REPLICA_STATE_CHANGED, $storeId); // avoids latency
            $this->replicaManager->syncReplicasToAlgolia($storeId, $this->productHelper->getIndexSettings($storeId));
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }
}
