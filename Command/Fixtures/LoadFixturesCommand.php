<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Fixtures;

use Doctrine\ORM\EntityManager;
use Nelmio\Alice\Persister\Doctrine;
use Oro\Bundle\ApplicationBundle\Tests\Behat\ReferenceRepositoryInitializer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Nelmio\Alice\Fixtures\Loader as AliceLoader;

class LoadFixturesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('gorgo:fixtures:load')
            ->addArgument('fixture', InputArgument::REQUIRED)
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, '', 'behat')
            ->setDescription('Loads Alice fixtures from Behat');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fixture = str_replace(':', '/Tests/Behat/Features/Fixtures/', $input->getArgument('fixture'));
        if ('@' === $fixture[0]) {
            $fixture = $this->getContainer()->get('kernel')->locateResource($fixture, null, true);
        }
        $loader = new AliceLoader();
        $loader->setLogger(new ConsoleLogger($output));
        $loader->setPersister(new Doctrine($this->getEntityManager(), true));
        $loader->addParser($this->getContainer()->get('gorgo.fixtures.yml_parser'));
        $references = new AliceCollection();
        $initializer = new ReferenceRepositoryInitializer(
            $this->getContainer()->get('kernel'),
            $references
        );
        $initializer->init();

        $loader->setReferences($references->toArray());
        $data = $loader->load($fixture);
        foreach ($data as $item) {
            $this->getEntityManager()->persist($item);
        }
        $this->getEntityManager()->flush();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
