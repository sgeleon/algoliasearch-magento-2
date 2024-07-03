<?php

namespace Algolia\AlgoliaSearch\Console\Command;

use Algolia\AlgoliaSearch\Api\Product\ReplicaManagerInterface;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ReplicaCommand extends Command
{
    const STORE_ARGUMENT = 'store';

    /**
     * @param ReplicaManagerInterface $replicaManager
     * @param string|null $name
     */
    public function __construct(
        protected ReplicaManagerInterface $replicaManager,
        ?string $name = null
    )
    {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('algolia:replicas:sync')
            ->setDescription('Sync configured sorting attributes in Magento to Algolia replica indices')
            ->setDefinition([
                new InputArgument(self::STORE_ARGUMENT, InputArgument::OPTIONAL, 'ID for store to be synced with Algolia (optional), if not specified all stores will be synced'),
            ]);

        parent::configure();
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeIds = $input->getArgument(self::STORE_ARGUMENT);

        if ($storeIds) {
            $output->writeln('<info>Syncing store ' . $storeIds . '!</info>');
        } else {
            $output->writeln('<info>Syncing all stores</info>');
        }

//        $this->replicaManager->syncReplicasToAlgolia();

        return Cli::RETURN_SUCCESS;
    }
}
