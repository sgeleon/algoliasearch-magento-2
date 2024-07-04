<?php

namespace Algolia\AlgoliaSearch\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

abstract class AbstractReplicaCommand extends Command
{
    protected const STORE_ARGUMENT = 'store';

    public function __construct(
        protected State $state,
        ?string         $name = null
    )
    {
        parent::__construct($name);
    }

    abstract protected function getReplicaCommandName(): string;

    abstract protected function getCommandDescription(): string;

    abstract protected function getStoreArgumentDescription(): string;

    abstract protected function getAdditionalDefinition(): array;

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $definition = [$this->getStoreArgumentDefinition()];
        $definition = array_merge($definition, $this->getAdditionalDefinition());

        $this->setName($this->getCommandName())
            ->setDescription($this->getCommandDescription())
            ->setDefinition($definition);

        parent::configure();
    }

    protected function getStoreArgumentDefinition(): InputArgument {
        return new InputArgument(
            self::STORE_ARGUMENT,
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            $this->getStoreArgumentDescription()
        );
    }

    public function getCommandName(): string
    {
        return 'algolia:replicas:' . $this->getReplicaCommandName();
    }

    protected function setAreaCode(): void
    {
        try {
            $this->state->setAreaCode(Area::AREA_CRONTAB);
        } catch (LocalizedException) {
            // Area code is already set - nothing to do
        }
    }

}
