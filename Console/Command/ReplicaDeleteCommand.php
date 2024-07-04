<?php

namespace Algolia\AlgoliaSearch\Console\Command;

use Algolia\AlgoliaSearch\Api\Product\ReplicaManagerInterface;
use Algolia\AlgoliaSearch\Exceptions\BadRequestException;
use Algolia\AlgoliaSearch\Service\StoreNameFetcher;
use Magento\Framework\Console\Cli;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ReplicaDeleteCommand extends Command
{
    protected const STORE_ARGUMENT = 'store';

    protected const UNUSED_OPTION = 'unused';
    protected const UNUSED_OPTION_SHORTCUT = 'u';

    protected ?OutputInterface $output = null;
    protected ?InputInterface $input = null;

    public function __construct(
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
        $unused = $input->getOption(self::UNUSED_OPTION);

        $msg = 'Deleting' . ($unused ? ' unused ': ' ') . 'replicas for ' . ($storeIds ? count($storeIds) : 'all') . ' store' . (!$storeIds || count($storeIds) > 1 ? 's' : '');
        if ($storeIds) {
            $output->writeln("<info>$msg: " . join(", ", $this->storeNameFetcher->getStoreNames($storeIds)) . '</info>');
        } else {
            $output->writeln("<info>$msg</info>");
        }

        $this->output = $output;
        $this->input = $input;

        if ($unused) {
            $unusedReplicas = $this->getUnusedReplicas($storeIds);
            if (!$unusedReplicas) {
                $output->writeln('<comment>No unused replicas found.</comment>');
                return Cli::RETURN_SUCCESS;
            }
            if (!$this->confirmDeleteUnused($unusedReplicas)) {
                return Cli::RETURN_SUCCESS;
            }
        }

        try {
            $this->deleteReplicas($storeIds, $unused);
        } catch (BadRequestException $e) {
            $this->output->writeln("<error>Error encountered while attempting to delete replica: {$e->getMessage()}</error>");
            $this->output->writeln('<comment>It is likely that the Magento integration does not manage the index. You should review your application configuration in Algolia.</comment>');
            return CLI::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }

    protected function getUnusedReplicas(array $storeIds): array
    {
        return array_reduce(
            $storeIds,
            function($allUnused, $storeId) {
                $unused = [];
                try {
                    $unused = $this->replicaManager->getUnusedReplicaIndices($storeId);
                } catch (\Exception $e) {
                    $this->output->writeln("<error>Unable to retrieve unused replicas for $storeId: {$e->getMessage()}</error>");
                }
                return array_unique(array_merge($allUnused, $unused));
            },
            []
        );
    }

    /**
     * Deleting unused replica indices is potentially risky, especially if they have enabled query suggestions on their index
     * Verify with the end user first!
     *
     * @param array $unusedReplicas
     * @return bool
     */
    protected function confirmDeleteUnused(array $unusedReplicas): bool
    {
        $this->output->writeln('<info>The following replicas appear to be unused and will be deleted:</info>');
        foreach ($unusedReplicas as $unusedReplica) {
            $this->output->writeln('<info> - ' . $unusedReplica . '</info>');
        }
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Do you want to proceed? (y/n)</question> ', false);

        if (!$helper->ask($this->input, $this->output, $question)) {
            $this->output->writeln('<comment>Operation cancelled.</comment>');
            return false;
        }
        return true;
    }

    protected function deleteReplicas(array $storeIds = [], bool $unused = false): void
    {
        if (count($storeIds)) {
            foreach ($storeIds as $storeId) {
                $this->deleteReplicasForStore($storeId, $unused);
            }
        } else {
            $this->deleteReplicasForAllStores($unused);
        }
    }

    protected function deleteReplicasForStore(int $storeId, bool $unused = false): void
    {
        $this->output->writeln('<info>Deleting' . ($unused ? ' unused ': ' ') . 'replicas for ' . $this->storeNameFetcher->getStoreName($storeId) . '...</info>');
        $this->replicaManager->deleteReplicasFromAlgolia($storeId, $unused);
    }

    protected function deleteReplicasForAllStores(bool $unused = false): void
    {
        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            $this->deleteReplicasForStore($storeId, $unused);
        }
    }
}
