<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml\Category;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Category;
use Algolia\AlgoliaSearch\Service\GetCurrentCategoryService;

class Merchandising extends \Magento\Backend\Block\Template
{
    /** @var string */
    protected $_template = 'catalog/category/edit/merchandising.phtml';

    /** @var GetCurrentCategoryService */
    protected $getCurrentCategoryService;

    /** @var ConfigHelper */
    private $configHelper;

    /** @var Data */
    private $coreHelper;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /**
     * @param Context $context
     * @param GetCurrentCategoryService $getCurrentCategoryService
     * @param ConfigHelper $configHelper
     * @param Data $coreHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        GetCurrentCategoryService $getCurrentCategoryService,
        ConfigHelper $configHelper,
        Data $coreHelper,
        array $data = []
    ) {
        $this->getCurrentCategoryService = $getCurrentCategoryService;
        $this->configHelper = $configHelper;
        $this->coreHelper = $coreHelper;
        $this->storeManager = $context->getStoreManager();

        parent::__construct($context, $data);
    }

    /** @return Category | null */
    public function getCategory()
    {
        $categoryId = $this->getRequest()->getParam('id');
        if (!$categoryId) {
            return null;
        }
        return $this->getCurrentCategoryService->getCategory($categoryId);
    }

    /** @return bool */
    public function isRootCategory()
    {
        $category = $this->getCategory();

        if ($category) {
            $path = $category->getPath();
            if (!$path) {
                return false;
            }
            $parts = explode('/', $path);
            if (count($parts) <= 2) {
                return true;
            }
        }

        return false;
    }

    /** @return ConfigHelper */
    public function getConfigHelper()
    {
        return $this->configHelper;
    }

    /** @return Data */
    public function getCoreHelper()
    {
        return $this->coreHelper;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @return \Magento\Store\Api\Data\StoreInterface|null
     */
    public function getCurrentStore()
    {
        if ($storeId = $this->getRequest()->getParam('store')) {
            return $this->storeManager->getStore($storeId);
        }

        return $this->storeManager->getDefaultStoreView();
    }

    /**
     * @return string
     */
    public function getPageModeOnly()
    {
        return Category::DM_PAGE;
    }

    /**
     * @return bool
     */
    public function canDisplayProducts()
    {
        if ($this->getCategory()->getDisplayMode() == $this->getPageModeOnly()) {
            return false;
        }

        return true;
    }
}
