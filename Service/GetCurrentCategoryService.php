<?php

namespace Algolia\AlgoliaSearch\Service;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class GetCurrentCategoryService
{
    /**
     * Current Category
     *
     * @var CategoryInterface
     */
    private $currentCategory;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param int $categoryId
     * @return CategoryInterface|null
     */
    public function getCategory(int $categoryId): ?CategoryInterface
    {
        if (!$this->currentCategory) {
            try {
                $this->currentCategory = $this->categoryRepository->get($categoryId);
            } catch (NoSuchEntityException $e) {
                return null;
            }
        }
        return $this->currentCategory;
    }
}
