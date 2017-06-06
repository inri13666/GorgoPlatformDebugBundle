<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Database;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineListConnectionsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('doctrine:connections');
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return class_exists('\Doctrine\DBAL\Connection');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Connection[] $connections */
        $connections = $this->getContainer()->get('doctrine')->getConnections();

        $table = new Table($output);
        $headers = ['Name', 'Driver'];
        if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
            $headers = ['Name', 'Driver', 'User', 'Password', 'Host', 'Port', 'DbName'];
        }
        $table->setHeaders($headers)->setRows([]);

        foreach ($connections as $name => $connection) {
            $row = [$name, $connection->getDriver()->getName()];
            if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $row[] = $connection->getUsername();
                $row[] = $connection->getPassword();
                $row[] = $connection->getHost();
                $row[] = $connection->getPort();
                $row[] = $connection->getDatabase();
            }
            $table->addRow($row);
        }

        $table->render();
    }
}
