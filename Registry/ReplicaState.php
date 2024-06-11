<?php

namespace Algolia\AlgoliaSearch\Registry;

class ReplicaState
{
    private ?array $originalSortConfiguration = null;
    private ?array $updatedSortConfiguration = null;

    public function getOriginalSortConfiguration(): array
    {
        return $this->originalSortConfiguration;
    }

    public function setOriginalSortConfiguration(array $originalSortConfiguration): void
    {
        $this->originalSortConfiguration = $originalSortConfiguration;
    }

    public function getUpdatedSortConfiguration(): array
    {
        return $this->updatedSortConfiguration;
    }

    public function setUpdatedSortConfiguration(array $updatedSortConfiguration): void
    {
        $this->updatedSortConfiguration = $updatedSortConfiguration;
    }

    public function isStateChanged(): bool
    {
        return $this->updatedSortConfiguration !== $this->originalSortConfiguration;
    }

}
