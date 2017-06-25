<?php

namespace Gorgo\Bundle\PlatformDebugBundle\MessageQueue\Command;

use Gorgo\Bundle\PlatformDebugBundle\MessageQueue\Extension\DebugExtension;
use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeCommand extends ConsumeMessagesCommand
{
    /** @var ExtensionInterface|DebugExtension */
    protected $debugExtension;

    /**
     * @param ExtensionInterface $extension
     */
    public function setExtension(ExtensionInterface $extension)
    {
        $this->debugExtension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'Show all logs in console')
            ->addOption('debug-excluded', null, InputOption::VALUE_OPTIONAL, 'Set excluded loggers chain, separated by common');
    }

    /**
     * {@inheritdoc}
     */
    protected function getLimitsExtensions(InputInterface $input, OutputInterface $output)
    {
        $extensions = parent::getLimitsExtensions($input, $output);

        if ($input->getOption('debug')) {
            if ($input->getOption('debug-excluded')) {
                $loggerChains = explode(',', $input->getOption('debug-excluded'));
                $this->debugExtension->setExcludedLoggerChain($loggerChains);
            }
            $extensions[] = $this->debugExtension;
        }

        return $extensions;
    }
}
