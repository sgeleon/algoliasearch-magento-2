<?php

namespace Algolia\AlgoliaSearch\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ReplicaCommand extends Command
{
    const STORE_ARGUMENT = 'store';

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
        $name = $input->getArgument(self::STORE_ARGUMENT);

        if ($name) {
            $output->writeln('<info>Syncing store ' . $name . '!</info>');
        } else {
            $output->writeln('<info>Syncing all stores</info>');
        }

        return Cli::RETURN_SUCCESS;
    }
}
