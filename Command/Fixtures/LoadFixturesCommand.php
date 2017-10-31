<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Fixtures;

use Doctrine\ORM\EntityManager;
use Gorgo\Bundle\PlatformDebugBundle\Fixtures\GorgoAliceLoader;
use Nelmio\Alice\Persister\Doctrine;
use Oro\Bundle\ApplicationBundle\Tests\Behat\ReferenceRepositoryInitializer;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AliceFixtureLoader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

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
            $fixture = trim($fixture, '\'""');
            if ('@' === $fixture[0]) {
                $fixture = $kernel->locateResource($fixture, null, true);
            }
            $fixtures[$k] = $fixture;
        }

        $loader = $this->getContainer()->get('gorgo.fixtures.loader');
        $loader->setLogger(new ConsoleLogger($output));

        $this->initReferences($loader, $this->getReferenceRepositoryInitializes());

        $references = $loader->getReferences();

        $this->getEntityManager()->beginTransaction();
        try {
            foreach ($fixtures as $fixture) {
                $loader->setReferences($references);
                $data = $loader->load($fixture);
                foreach ($data as $item) {
                    $this->getEntityManager()->persist($item);
                }
                $this->getEntityManager()->flush();
                $references = array_merge($references, $data);
            }
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }

    /**
     * @param GorgoAliceLoader $aliceLoader
     * @param array|ReferenceRepositoryInitializerInterface[] $referenceRepositoryInitializes
     */
    protected function initReferences(GorgoAliceLoader $aliceLoader, array $referenceRepositoryInitializes = [])
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $aliceLoader->setDoctrine($doctrine);

        $referenceRepository = $aliceLoader->getReferenceRepository();
        $referenceRepository->clear();

        foreach ($referenceRepositoryInitializes as $initializer) {
            $initializer->init($doctrine, $referenceRepository);
        }
    }

    /**
     * @return array
     */
    protected function getReferenceRepositoryInitializes()
    {
        $kernel = $this->getContainer()->get('kernel');
        $initializes = array();
        /** @var BundleInterface $bundle */
        foreach ($kernel->getBundles() as $bundle) {
            $namespace = sprintf('%s\Tests\Behat\ReferenceRepositoryInitializer', $bundle->getNamespace());

            if (!class_exists($namespace)) {
                continue;
            }

            try {
                $initializes[] = new $namespace;
            } catch (\Throwable $e) {
            }
        }

        return $initializes;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
