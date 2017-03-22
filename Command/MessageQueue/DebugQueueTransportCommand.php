<?php
namespace Gorgo\Bundle\PlatformDebugBundle\Command\MessageQueue;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class DebugQueueTransportCommand extends ContainerAwareCommand
{
    const SERVICE_PREFIX = 'oro_message_queue.transport.';
    const SERVICE_SUFFIX = '.connection';

    /** @var ContainerBuilder */
    private $containerBuilder;

    protected function configure()
    {
        $this
            ->setName('gorgo:debug:message-queue:transport')
            ->setDescription('Displays the list of all defined queue transports');
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled()
    {
        return $this->getApplication()->getKernel()->isDebug();
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list = $this->findServiceIds();

        $table = new Table($output);
        $table->addRow([])->setHeaders([
            'Service ID',
            'Transport Class',
            'Is Alias Of',
        ]);
        $builder = $this->getContainerBuilder();
        foreach ($list as $serviceId) {
            $realService = $serviceId;
            if (!$builder->hasDefinition($serviceId)) {
                if ($builder->hasAlias($serviceId)) {
                    $realService = (string)$builder->getAlias($serviceId);
                }
            }
            $table->addRow([
                $serviceId,
                $builder->getDefinition($realService)->getClass(),
                ($serviceId === $realService) ? '' : $realService,
            ]);
        }
        $table->render();
        $output->writeln('');
    }

    /**
     * Loads the ContainerBuilder from the cache.
     *
     * @return ContainerBuilder
     *
     * @throws \LogicException
     */
    protected function getContainerBuilder()
    {
        if ($this->containerBuilder) {
            return $this->containerBuilder;
        }

        if (!$this->getApplication()->getKernel()->isDebug()) {
            throw new \LogicException(
                sprintf('Debug information about the container is only available in debug mode.')
            );
        }

        if (!is_file($cachedFile = $this->getContainer()->getParameter('debug.container.dump'))) {
            throw new \LogicException(
                'Debug information about the container could not be found. Please clear the cache and try again.'
            );
        }

        $container = new ContainerBuilder();

        $loader = new XmlFileLoader($container, new FileLocator());
        $loader->load($cachedFile);

        return $this->containerBuilder = $container;
    }

    private function findServiceIds()
    {
        $serviceIds = $this->getContainerBuilder()->getServiceIds();
        $foundServiceIds = array();
        foreach ($serviceIds as $serviceId) {
            if ((0 !== strpos($serviceId, self::SERVICE_PREFIX)) ||
                (false === strpos($serviceId, self::SERVICE_SUFFIX))
            ) {
                continue;
            }
            $foundServiceIds[] = $serviceId;
        }

        return $foundServiceIds;
    }
}
