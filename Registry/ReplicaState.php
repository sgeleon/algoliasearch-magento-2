<?php

namespace Algolia\AlgoliaSearch\Registry;

class ReplicaState
{
    public const REPLICA_STATE_UNCHANGED = 1;
    public const REPLICA_STATE_CHANGED = 2;
    public const REPLICA_STATE_UNKNOWN = 3;

    private array $_scopeState = [];

    private array $_scopeConfigurationOld = [];

    private array $_scopeConfigurationNew = [];
    
    public function getOriginalSortConfiguration(int $storeId): array
    {
        return $this->_scopeConfigurationOld[$storeId] ?? [];
    }

    public function setOriginalSortConfiguration(array $originalSortConfiguration, int $storeId): void
    {
        $this->_scopeConfigurationOld[$storeId] = $originalSortConfiguration;
    }

    public function getUpdatedSortConfiguration(int $storeId): array
    {
        return $this->_scopeConfigurationNew[$storeId] ?? [];
    }

    public function setUpdatedSortConfiguration(array $updatedSortConfiguration, int $storeId): void
    {
        $this->_scopeConfigurationNew[$storeId] = $updatedSortConfiguration;
    }

    protected function hasConfigDataToCompare(int $storeId) {
        return isset($this->_scopeConfigurationOld[$storeId])
            && isset($this->_scopeConfigurationNew[$storeId]);
    }

    public function getChangeState(int $storeId): int
    {
        $state = $this->_scopeState[$storeId] ?? self::REPLICA_STATE_UNKNOWN;
        if ($state === self::REPLICA_STATE_UNKNOWN && $this->hasConfigDataToCompare($storeId)) {
            $state = $this->getOriginalSortConfiguration($storeId) === $this->getUpdatedSortConfiguration($storeId)
                ? self::REPLICA_STATE_UNCHANGED
                : self::REPLICA_STATE_CHANGED;
        }
        return $state;
    }

    public function setChangeState(int $state, int $storeId): void
    {
        if ($state < self::REPLICA_STATE_UNCHANGED || $state > self::REPLICA_STATE_UNKNOWN) {
            $state = self::REPLICA_STATE_UNKNOWN;
        }
        $this->_scopeState[$storeId] = $state;
    }

}
