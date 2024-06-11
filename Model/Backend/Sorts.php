<?php

namespace Algolia\AlgoliaSearch\Model\Backend;

use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Algolia\AlgoliaSearch\Registry\ReplicaState;
use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;

class Sorts extends ArraySerialized
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param Json|null $serializer
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param ProductHelper $productHelper
     */
    public function __construct(
        Context                         $context,
        Registry                        $registry,
        ScopeConfigInterface            $config,
        TypeListInterface               $cacheTypeList,
        protected StoreManagerInterface $storeManager,
        protected Data                  $helper,
        protected ProductHelper         $productHelper,
        protected ReplicaState          $replicaState,
        AbstractResource                $resource = null,
        AbstractDb                      $resourceCollection = null,
        array                           $data = [],
        Json                            $serializer = null
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
     * @throws AlgoliaException
     * @throws NoSuchEntityException|LocalizedException
     */
    public function afterSave(): \Magento\Framework\App\Config\Value
    {
        if ($this->isValueChanged()) {
            $this->replicaState->setOriginalSortConfiguration($this->serializer->unserialize($this->getOldValue()));
            $this->replicaState->setUpdatedSortConfiguration($this->serializer->unserialize($this->getValue()));
        }

        return parent::afterSave();
    }
}
