<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Behat;

use Gorgo\Bundle\PlatformDebugBundle\Fixtures\GorgoAliceLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

class DebugReferenceRepositoryCommand extends ContainerAwareCommand
{
    const NAME = 'gorgo:debug:reference-repository';

    /** @var KernelInterface */
    protected $kernel;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $loader = $this->getLoader();
        $this->initReferences($loader, $this->getReferenceRepositoryInitializes());

        foreach ($loader->getReferences() as $reference => $object) {
            $table->addRow([
                (string)$reference,
                ClassUtils::getRealClass($object),
            ]);
        }

        $table->render();
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return GorgoAliceLoader|object
     */
    protected function getLoader()
    {
        return $this->getContainer()->get('gorgo.fixtures.loader');
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
}
