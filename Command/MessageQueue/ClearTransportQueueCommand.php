<?php
namespace Gorgo\Bundle\PlatformDebugBundle\Command\MessageQueue;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalLazyConnection;

class ClearTransportQueueCommand extends ContainerAwareCommand
{
    const SERVICE_PREFIX = 'oro_message_queue.transport.';
    const SERVICE_SUFFIX = '.connection';

    protected function configure()
    {
        $this
            ->setName('gorgo:message-queue:transport:clear')
            ->setDescription('Removes all queued messages for specific transport (Supported only DBAL)')
            ->addArgument(
                'transport',
                InputArgument::OPTIONAL,
                'Transport to be released'
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, "");
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('force')) {
            $output->writeln('--force required');

            return 1;
        }

        $transport = $input->getArgument('transport') ?: $this->getDefaultTransport();

        if (0 !== strpos(self::SERVICE_PREFIX, $transport)) {
            $transport = self::SERVICE_PREFIX . $transport;
        }
        if (false === strpos(self::SERVICE_SUFFIX, $transport)) {
            $transport = $transport . self::SERVICE_SUFFIX;
        }

        if (!$this->getContainer()->has($transport)) {
            $output->writeln(sprintf('Service %s not found', $transport));

            return 1;
        }
        /** @var DbalConnection $transportService */
        $transportService = $this->getContainer()->get($transport);
        if (!in_array(get_class($transportService), $this->getSupportedTransports(), true)) {
            $output->writeln(sprintf('Service %s is not supported', $transport));

            return -1;
        }

        $connection = $transportService->getDBALConnection();

        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        $affected = $connection->executeUpdate(
            $connection->getDatabasePlatform()->getTruncateTableSQL($transportService->getTableName())
        );
        $connection->query('SET FOREIGN_KEY_CHECKS=1');
        $output->writeln(sprintf('Removed %d messages from queue', $affected));
    }

    /**
     * @return array
     */
    protected function getSupportedTransports()
    {
        return [
            DbalConnection::class,
            DbalLazyConnection::class
        ];
    }

    /**
     * @return string
     */
    protected function getDefaultTransport()
    {
        return $this->getContainer()->getParameter('message_queue_transport');
    }
}
