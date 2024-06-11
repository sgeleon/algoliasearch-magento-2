<?php

namespace Algolia\AlgoliaSearch\Model\Backend;

use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
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
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        protected StoreManagerInterface $storeManager,
        protected Data $helper,
        protected ProductHelper $productHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        Json $serializer = null
    ) {
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
     * @throws NoSuchEntityException
     */
    public function afterSave()
    {
        if ($this->isValueChanged()) {
            try{
                $oldValue = $this->serializer->unserialize($this->getOldValue());
                $updatedValue = $this->serializer->unserialize($this->getValue());
                $sortingAttributes = array_merge($oldValue, $updatedValue);
                $storeIds = array_keys($this->storeManager->getStores());
                foreach ($storeIds as $storeId) {
                    $indexName = $this->helper->getIndexName($this->productHelper->getIndexNameSuffix(), $storeId);
                    $this->productHelper->handlingReplica($indexName, $storeId, $sortingAttributes);
                }
            } catch (AlgoliaException $e) {
                if ($e->getCode() !== 404) {
                    throw $e;
                }
            }
        }
        return parent::afterSave();
    }
}
