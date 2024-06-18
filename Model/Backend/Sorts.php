<?php

namespace Algolia\AlgoliaSearch\Model\Backend;

use Algolia\AlgoliaSearch\Helper\Configuration\ConfigChecker;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Algolia\AlgoliaSearch\Registry\ReplicaState;
use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Sorts extends ArraySerialized
{
    public function __construct(
        Context                              $context,
        Registry                             $registry,
        ScopeConfigInterface                 $config,
        TypeListInterface                    $cacheTypeList,
        protected StoreManagerInterface      $storeManager,
        protected Data                       $helper,
        protected ProductHelper              $productHelper,
        protected WebsiteRepositoryInterface $websiteRepository,
        protected ReplicaState               $replicaState,
        protected ConfigChecker              $configChecker,
        AbstractResource                     $resource = null,
        AbstractDb                           $resourceCollection = null,
        array                                $data = [],
        Json                                 $serializer = null
    )
    {
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data,
            $serializer);
    }

    /**
     * @return $this
     * @throws NoSuchEntityException
     */
    public function afterSave(): \Magento\Framework\App\Config\Value
    {
        $storeIds = $this->getAffectedStoreIds();
        foreach ($storeIds as $storeId) {
            if ($this->isValueChanged()) {
                $this->replicaState->setChangeState(ReplicaState::REPLICA_STATE_CHANGED, $storeId);
                $this->replicaState->setOriginalSortConfiguration($this->serializer->unserialize($this->getOldValue()), $storeId);
                $this->replicaState->setUpdatedSortConfiguration($this->serializer->unserialize($this->getValue()), $storeId);
            } else {
                $this->replicaState->setChangeState(ReplicaState::REPLICA_STATE_UNCHANGED, $storeId);
            }
        }

        return parent::afterSave();
    }

    /**
     * For the current operation's scope determine which stores need to be updated
     * @return int[]
     * @throws NoSuchEntityException
     */
    public function getAffectedStoreIds(): array
    {
        $scopeId = $this->getScopeId();
        $scope = $this->getScope();
        $storeIds = [];

        switch ($scope) {
            // check and find all scopes that are not overridden
            case ScopeConfigInterface::SCOPE_TYPE_DEFAULT:
                $storeIds = array_keys($this->storeManager->getStores());
                $this->configChecker->checkAndApplyAllScopes(
                    $this->getPath(),
                    function (string $scope, int $scopeId) use (&$storeIds) {
                        if ($scope === ScopeInterface::SCOPE_STORES) {
                            // remove overridden store (not in scope of this change)
                            if (($key = array_search($scopeId, $storeIds)) !== false) {
                                unset($storeIds[$key]);
                            }
                        }
                        elseif ($scope === ScopeInterface::SCOPE_WEBSITES) {
                            // website overridden - remove all the stores (not in scope)
                            $website = $this->websiteRepository->getById($scopeId);
                            foreach ($website->getStores() as $store) {
                                if (($key = array_search($store->getId(), $storeIds)) !== false) {
                                    unset($storeIds[$key]);
                                }
                            }
                        }
                    },
                    false
                );
                break;

            // website config applied - check and find all stores that are not overridden
            case ScopeInterface::SCOPE_WEBSITES:
                $website = $this->websiteRepository->getById($scopeId);
                foreach ($website->getStores() as $store) {
                    if (!$this->configChecker->isSettingAppliedForScopeAndCode($this->getPath(), ScopeInterface::SCOPE_STORES, $store->getId())) {
                        $storeIds[] = $store->getId();
                    }
                }
                break;
            // store override only
            case ScopeInterface::SCOPE_STORES:
                $storeIds[] = $scopeId;
        }
        return $storeIds;
    }

    /**
     * For a given website perform an operation on each of the corresponding stores
     * based on whether the store has overridden the config.
     *
     * @param int $websiteId
     * @param callable $callback Callback to execute for a given store
     *                           Signature: function(int $storeId, bool $isConfigOverridden)
     * @return void
     * @throws NoSuchEntityException
     */
    protected function handleStoresForWebsite(int $websiteId, callable $callback): void
    {
        $website = $this->websiteRepository->getById($websiteId);
        foreach ($website->getStores() as $store) {
            $callback(
                $store->getId(),
                $this->configChecker->isSettingAppliedForScopeAndCode(
                    $this->getPath(),
                    ScopeInterface::SCOPE_STORES, $store->getId())
            );
        }
    }
}
