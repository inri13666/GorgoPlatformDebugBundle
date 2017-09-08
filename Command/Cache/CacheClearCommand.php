<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Cache;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CacheClearCommand extends \Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand
{
    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = sprintf('rm -rf %s', $this->getContainer()->get('kernel')->getCacheDir());
        if ('WINNT' === PHP_OS) {
            $command = sprintf(
                'rd /Q /S %s',
                str_replace('/', DIRECTORY_SEPARATOR, $this->getContainer()->get('kernel')->getCacheDir())
            );
        }
        $p = new Process($command, null, null, null, null);
        $p->run();
        $output->writeln(sprintf('Cache cleared for %s', $this->getContainer()->get('kernel')->getEnvironment()));
    }
}
