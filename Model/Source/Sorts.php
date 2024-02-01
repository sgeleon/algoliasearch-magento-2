<?php

namespace Algolia\AlgoliaSearch\Model\Source;

/**
 * Algolia custom sort order field
 */
class Sorts extends AbstractTable
{
    protected function getTableData()
    {
        $productHelper = $this->productHelper;

        return [
            'attribute' => [
                'label'  => 'Attribute',
                'values' => function () use ($productHelper) {
                    $options = [];

                    $attributes = $productHelper->getAllAttributes();

                    foreach ($attributes as $key => $label) {
                        $options[$key] = $key ? $key : $label;
                    }

                    return $options;
                },
            ],
            'sort' => [
                'label'  => 'Sort',
                'values' => ['asc' => __('Ascending'), 'desc' => __('Descending')],
            ],
            'sortLabel' => [
                'label' => 'Label',
            ],
            'virtualReplica' => [
                'label' => 'Enable Virtual Replica?',
                'values' => ['0' => __('No'), '1' => __('Yes')],
            ],
        ];
    }
}
