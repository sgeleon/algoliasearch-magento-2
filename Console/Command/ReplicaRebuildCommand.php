<?php

namespace Algolia\AlgoliaSearch\Console\Command;

class ReplicaRebuildCommand extends AbstractReplicaCommand
{
    protected function getReplicaCommandName(): string
    {
        return 'rebuild';
    }

    protected function getCommandDescription(): string {
        return 'Rebuild replica configuration for Magento sorting attributes';
    }

    protected function getStoreArgumentDescription(): string
    {
       return 'ID(s) for store(s) to rebuild replicas';
    }

    protected function getAdditionalDefinition(): array
    {
       return [];
    }

}
