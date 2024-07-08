<?php

namespace Algolia\AlgoliaSearch\Console\Command;

use Algolia\AlgoliaSearch\Api\Console\ReplicaDeleteCommandInterface;
use Algolia\AlgoliaSearch\Api\Console\ReplicaSyncCommandInterface;
use Algolia\AlgoliaSearch\Api\Product\ReplicaManagerInterface;
use Algolia\AlgoliaSearch\Console\Traits\ReplicaDeleteCommandTrait;
use Algolia\AlgoliaSearch\Console\Traits\ReplicaSyncCommandTrait;
use Algolia\AlgoliaSearch\Exception\ReplicaLimitExceededException;
use Algolia\AlgoliaSearch\Exceptions\BadRequestException;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Algolia\AlgoliaSearch\Service\StoreNameFetcher;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReplicaRebuildCommand
    extends AbstractReplicaCommand
    implements ReplicaSyncCommandInterface, ReplicaDeleteCommandInterface
{
    use ReplicaSyncCommandTrait;
    use ReplicaDeleteCommandTrait;

    public function __construct(
        protected ReplicaManagerInterface $replicaManager,
        protected StoreNameFetcher        $storeNameFetcher,
        protected ProductHelper           $productHelper,
        State                             $state,
        ?string                           $name = null
    )
    {
        parent::__construct($state, $name);
    }

    protected function getReplicaCommandName(): string
    {
        return 'rebuild';
    }

    protected function getCommandDescription(): string
    {
        return "Delete and rebuild replica configuration for Magento sorting attributes (only run this operation if errors are encountered during regular sync)";
    }

    protected function getStoreArgumentDescription(): string
    {
        return 'ID(s) for store(s) to rebuild replicas (optional), if not specified all store replicas will be rebuilt';
    }

    protected function getAdditionalDefinition(): array
    {
        return [];
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->setAreaCode();

        $storeIds = $this->getStoreIds($input);

        $msg = 'Rebuilding replicas for ' . ($storeIds ? count($storeIds) : 'all') . ' store' . (!$storeIds || count($storeIds) > 1 ? 's' : '');
        if ($storeIds) {
            $output->writeln("<info>$msg: " . join(", ", $this->storeNameFetcher->getStoreNames($storeIds)) . '</info>');
        } else {
            $output->writeln("<info>$msg</info>");
        }

        $this->deleteReplicas($storeIds);
        try {
            $this->syncReplicas($storeIds);
        } catch (ReplicaLimitExceededException $e) {
            $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->output->writeln('<comment>Reduce the number of sorting attributes that have enabled virtual replicas and try again.</comment>');
            return CLI::RETURN_FAILURE;
        } catch (BadRequestException $e) {
            $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            if ($storeIds) {
                $this->output->writeln('<comment>Your Algolia application may contain cris-crossed replicas. Try running "algolia:replicas:rebuild" for all stores to correct this.');
            }
            return CLI::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }

}
