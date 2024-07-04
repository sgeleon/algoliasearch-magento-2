<?php

namespace Algolia\AlgoliaSearch\Console\Command;

use Algolia\AlgoliaSearch\Api\Product\ReplicaManagerInterface;
use Algolia\AlgoliaSearch\Service\StoreNameFetcher;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReplicaDeleteCommand extends Command
{
    protected const STORE_ARGUMENT = 'store';

    protected const UNUSED_OPTION = 'unused';
    protected const UNUSED_OPTION_SHORTCUT = 'u';

    protected ?OutputInterface $output = null;

    public function __construct(
        protected State                   $state,
        protected ReplicaManagerInterface $replicaManager,
        protected StoreManagerInterface   $storeManager,
        protected StoreNameFetcher        $storeNameFetcher,
        ?string                           $name = null
    )
    {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('algolia:replicas:delete')
            ->setDescription('Delete associated replica indices in Algolia')
            ->setDefinition([
                new InputArgument(
                    self::STORE_ARGUMENT,
                    InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                    'ID(s) for store(s) to delete replicas in Algolia (optional), if not specified, replicas for all stores will be deleted'
                ),
                new InputOption(
                    self::UNUSED_OPTION,
                    '-' . self::UNUSED_OPTION_SHORTCUT,
                    InputOption::VALUE_NONE,
                    'Delete unused replicas only'
                )
            ]);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeIds = (array) $input->getArgument(self::STORE_ARGUMENT);

        $msg = 'Deleting replicas for ' . ($storeIds ? count($storeIds) : 'all') . ' store' . (!$storeIds || count($storeIds) > 1 ? 's' : '');
        if ($storeIds) {
            $output->writeln("<info>$msg: " . join(", ", $this->storeNameFetcher->getStoreNames($storeIds)) . '</info>');
        } else {
            $output->writeln("<info>$msg</info>");
        }

        $this->output = $output;
//        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $this->deleteReplicas($storeIds);

        return Cli::RETURN_SUCCESS;
    }


    protected function deleteReplicas(array $storeIds = [], bool $unusedOnly = false): void
    {
        if (count($storeIds)) {
            foreach ($storeIds as $storeId) {
                $this->deleteReplicasForStore($storeId);
            }
        } else {
            $this->deleteReplicasForAllStores();
        }
    }

    protected function deleteReplicasForStore(int $storeId): void
    {
        $this->output->writeln('<info>Deleting replicas for ' . $this->storeNameFetcher->getStoreName($storeId) . '...</info>');
        $this->replicaManager->deleteReplicasFromAlgolia($storeId);
    }

    protected function deleteReplicasForAllStores(): void
    {
        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            $this->deleteReplicasForStore($storeId);
        }
    }
}
