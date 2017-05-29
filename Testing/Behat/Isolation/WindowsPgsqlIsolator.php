<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Testing\Behat\Isolation;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\AbstractOsRelatedIsolator;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\IsolatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class WindowsPgsqlIsolator extends AbstractOsRelatedIsolator implements IsolatorInterface
{
    const TIMEOUT = 120;
    const DROPDB = 'cmd /c set PGPASSWORD="%s" && cmd /c dropdb --host=%s --port=%s --username=%s %s';
    const CREATEDB = 'cmd /c set PGPASSWORD="%s"' .
    ' && cmd /c createdb --host=%s --port=%s --username=%s --owner=%s --template=%s %s';

    /** @var string */
    protected $dbHost;

    /** @var  string */
    protected $dbPort;

    /** @var string */
    protected $dbName;

    /** @var string */
    protected $dbTempName;

    /** @var string */
    protected $dbPass;

    /** @var string */
    protected $dbUser;

    /** @var string */
    protected $dbTemp;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $kernel->boot();
        $container = $kernel->getContainer();

        $this->dbHost = $container->getParameter('database_host');
        $this->dbPort = $container->getParameter('database_port');
        $this->dbName = $container->getParameter('database_name');
        $this->dbUser = $container->getParameter('database_user');
        $this->dbPass = $container->getParameter('database_password');
        $this->dbTemp = $this->dbName . TokenGenerator::generateToken('db');
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return
            self::isApplicableOS()
            && DatabaseDriverInterface::DRIVER_POSTGRESQL === $container->getParameter('database_driver');
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return 'PostgreSql Db';
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Dumping current application database (PostgreSQL)</info>');
        $this->makeDump();
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $this->dropDb();
        $this->restoreDbFromDump();
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
        $event->writeln('<info>Remove PostgreSQL Db dump</info>');
        $this->dropTempDb();
    }

    /** {@inheritdoc} */
    public function restoreState(RestoreStateEvent $event)
    {
        $event->writeln('<info>Begin to restore the state of PostgreSQL Db...</info>');

        $event->writeln('<info>Drop PostgreSQL Db</info>');
        $this->dropDb();

        $event->writeln('<info>Restore PostgreSQL Db from dump</info>');
        $this->restoreDbFromDump();

        $event->writeln('<info>Remove PostgreSQL Db dump</info>');
        $this->dropTempDb();

        $event->writeln('<info>PostgreSQL Db was restored from dump</info>');
    }

    /** {@inheritdoc} */
    public function isOutdatedState()
    {
        try {
            $this->makeDump();
            $this->dropTempDb();

            return false;
        } catch (ProcessFailedException $e) {
            return true;
        }
    }

    /** {@inheritdoc} */
    public function getTag()
    {
        return 'database';
    }

    /** {@inheritdoc} */
    protected function getApplicableOs()
    {
        return [
            AbstractOsRelatedIsolator::WINDOWS_OS,
        ];
    }

    /**
     * @param string $commandline The command line to run
     * @param int $timeout The timeout in seconds
     *
     * @return Process
     */
    protected function runProcess($commandline, $timeout = 120)
    {
        $process = new Process($commandline);

        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }

    protected function dropDb()
    {
        $this->killConnections();

        $process = sprintf(
            self::DROPDB,
            $this->dbPass,
            $this->dbHost,
            $this->dbPort,
            $this->dbUser,
            $this->dbName
        );
        $this->runProcess($process);
    }

    protected function dropTempDb()
    {
        $process = sprintf(
            self::DROPDB,
            $this->dbPass,
            $this->dbHost,
            $this->dbPort,
            $this->dbUser,
            $this->dbTemp
        );
        $this->runProcess($process);
    }

    /** {@inheritdoc} */
    protected function restoreDbFromDump()
    {
        $this->killConnections();

        $process = sprintf(
            self::CREATEDB,
            $this->dbPass,
            $this->dbHost,
            $this->dbPort,
            $this->dbUser,
            $this->dbUser,
            $this->dbTemp,
            $this->dbName
        );
        $this->runProcess($process);
    }

    /** {@inheritdoc} */
    protected function makeDump()
    {
        $this->killConnections();

        $process = sprintf(
            self::CREATEDB,
            $this->dbPass,
            $this->dbHost,
            $this->dbPort,
            $this->dbUser,
            $this->dbUser,
            $this->dbName,
            $this->dbTemp
        );
        $this->runProcess($process);
    }

    private function killConnections()
    {
        $process = sprintf(
            'cmd /c set PGPASSWORD="%s" && cmd /c psql -h %s -U %s template1 -c "' .
            'SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = \'%s\'"',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbName
        );
        $this->runProcess($process);
    }
}
