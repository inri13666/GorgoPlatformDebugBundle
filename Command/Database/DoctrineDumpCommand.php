<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Database;

use Doctrine\DBAL\Connection;
use Gorgo\Bundle\PlatformDebugBundle\Model\Database\DatabaseConfigurationModel;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineDumpCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('gorgo:isolator:doctrine:dump')
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, '', null)
            ->addOption('sid', null, InputOption::VALUE_OPTIONAL, '', (new \DateTime())->format('Ymdhis'));
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
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection($input->getOption('connection'));

        $configuration = new DatabaseConfigurationModel();
        $configuration->setDbName($connection->getDatabase())
            ->setDriver($connection->getDriver()->getName())
            ->setHost($connection->getHost())
            ->setPort($connection->getPort())
            ->setUser($connection->getUsername())
            ->setPassword($connection->getPassword());

        $isolator = $this->getContainer()->get('oro_debug.database.isolation.isolator.registry')
            ->findIsolator($configuration);
        $sid = $input->getOption('sid');
        $isolator->dump($sid, $configuration);
        $output->writeln(sprintf('Generated dump with sid <info>%s</info>', $sid));
    }
}
