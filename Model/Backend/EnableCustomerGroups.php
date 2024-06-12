<?php

namespace Algolia\AlgoliaSearch\Model\Backend;

use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

class EnableCustomerGroups extends Value
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param ProductHelper $productHelper
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context                         $context,
        Registry                        $registry,
        ScopeConfigInterface            $config,
        TypeListInterface               $cacheTypeList,
        protected StoreManagerInterface $storeManager,
        protected Data                  $helper,
        protected ProductHelper         $productHelper,
        AbstractResource                $resource = null,
        AbstractDb                      $resourceCollection = null,
        array                           $data = []
    )
    {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return $this
     * @throws AlgoliaException
     * @throws NoSuchEntityException
     */
    public function afterSave(): \Magento\Framework\App\Config\Value
    {
        // TODO: Determine if this action should be performed in the event of customer group pricing enablement
        /*
        if ($this->isValueChanged()) {
            try {
                $storeIds = array_keys($this->storeManager->getStores());
                foreach ($storeIds as $storeId) {
                    $indexName = $this->helper->getIndexName($this->productHelper->getIndexNameSuffix(), $storeId);
                    $this->productHelper->handlingReplica($indexName, $storeId);
                }
            } catch (AlgoliaException $e) {
                if ($e->getCode() !== 404) {
                    throw $e;
                }
            }
        }
        */

        return parent::afterSave();
    }
}

