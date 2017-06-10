<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Config;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DebugConfigCommand extends ContainerAwareCommand
{
    /**
     * {@internaldoc}
     */
    protected function configure()
    {
        $this->setName('oro:debug:config')
            ->addOption('parameter', 'p', InputOption::VALUE_REQUIRED, '');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $parameter = $input->getOption('parameter');
        $value = $configManager->get($parameter);
        $output->write(sprintf(
            'Config value for "%s" ',
            $parameter
        ));
        if (is_array($value)) {
            $output->writeln('');
            $output->writeln(sprintf('"%s"', var_export($value)));

            return;
        }
        if (is_object($value)) {
            $output->writeln(sprintf('instance of "%s"', get_class($value)));
            $output->writeln(sprintf('"%s"', var_export($value)));

            return;
        }
        $output->writeln(sprintf(': "%s"', $value));
    }
}
