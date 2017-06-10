<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Container;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParameterCommand extends ContainerAwareCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('gorgo:parameter')
            ->addOption('parameter', 'p', InputOption::VALUE_REQUIRED, '');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                'Parameter %s : %s',
                $input->getOption('parameter'),
                $this->getContainer()->getParameter($input->getOption('parameter'))
            )
        );
    }
}
