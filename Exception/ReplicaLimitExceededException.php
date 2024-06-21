<?php

namespace Algolia\AlgoliaSearch\Exception;

use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;

class ReplicaLimitExceededException extends AlgoliaException
{
    protected int $replicaCount = 0;

    public function withReplicaCount(int $replicaCount): ReplicaLimitExceededException
    {
        $this->replicaCount = $replicaCount;
        return $this;
    }

    public function getReplicaCount(): int {
        return $this->replicaCount;
    }

}
