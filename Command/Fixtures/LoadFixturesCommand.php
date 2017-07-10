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
            ->addArgument('fixture', InputArgument::IS_ARRAY | InputArgument::REQUIRED)
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, '', 'behat')
            ->setDescription('Loads Alice fixtures from Behat');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fixtures = $input->getArgument('fixture');
        $kernel = $this->getContainer()->get('kernel');
        foreach ($fixtures as $k => $fixture) {
            $fixture = str_replace(':', '/Tests/Behat/Features/Fixtures/', $fixture);
            if ('@' === $fixture[0]) {
                $fixture = $kernel->locateResource($fixture, null, true);
            }
            $fixtures[$k] = $fixture;
        }
        $loader = $this->getContainer()->get('gorgo.fixtures.loader');
        $loader->setLogger(new ConsoleLogger($output));
        $references = new AliceCollection();
        $initializer = new ReferenceRepositoryInitializer($kernel, $references);
        $initializer->init();

        $references = $references->toArray();
        foreach ($fixtures as $fixture) {
            $loader->setReferences($references);
            $data = $loader->load($fixture);
            foreach ($data as $item) {
                $this->getEntityManager()->persist($item);
            }
            $this->getEntityManager()->flush();
            $references = array_merge($references, $data);
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
