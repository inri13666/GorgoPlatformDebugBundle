<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Isolator\Database;

use Gorgo\Bundle\PlatformDebugBundle\Model\Database\DatabaseConfigurationInterface;
use Gorgo\Bundle\PlatformDebugBundle\Service\Database\ProcessExecutor;

class PgsqlDatabaseIsolator extends AbstractDatabaseIsolator
{
    /**
     * @var string
     */
    protected $dropdbBin = 'dropdb';

    /**
     * @var string
     */
    protected $createdbBin = 'createdb';

    /**
     * @var string
     */
    protected $psqlBin = 'psql';

    /**
     * @var ProcessExecutor
     */
    protected $processExecutor;

    /**
     * @param ProcessExecutor $processExecutor
     */
    public function __construct(ProcessExecutor $processExecutor)
    {
        $this->processExecutor = $processExecutor;
    }

    /**
     * @param string $dropdbBin
     *
     * @return $this
     */
    public function setDropdbBin($dropdbBin)
    {
        $this->dropdbBin = $dropdbBin;

        return $this;
    }

    /**
     * @param string $createdbBin
     *
     * @return $this
     */
    public function setCreatedbBin($createdbBin)
    {
        $this->createdbBin = $createdbBin;

        return $this;
    }

    /**
     * @param string $psqlBin
     *
     * @return $this
     */
    public function setPsqlBin($psqlBin)
    {
        $this->psqlBin = $psqlBin;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dump($id, DatabaseConfigurationInterface $databaseConfiguration)
    {
        $setPasswordCommand = null;

        if ($databaseConfiguration->getPassword()) {
            putenv(sprintf('PGPASSWORD=%s', $databaseConfiguration->getPassword()));
        }

        $database = $this->getBackupDbName($id, $databaseConfiguration);

        $user = $this->resolveUser($databaseConfiguration);
        $host = $this->resolveHost($databaseConfiguration);
        $port = $this->resolvePort($databaseConfiguration);

        if ($this->verify($databaseConfiguration->getDbName(), $databaseConfiguration)) {
            $this->processExecutor->execute($this->getKillConnectionsCommand($user, $host, $port, $database));
            $this->drop($database, $databaseConfiguration);
            $this->processExecutor->execute(
                $this->getDumpCommand($user, $host, $port, $databaseConfiguration->getDbName(), $database)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restore($id, DatabaseConfigurationInterface $databaseConfiguration)
    {
        if ($databaseConfiguration->getPassword()) {
            putenv(sprintf('PGPASSWORD=%s', $databaseConfiguration->getPassword()));
        }

        $database = $this->getBackupDbName($id, $databaseConfiguration);

        $user = $this->resolveUser($databaseConfiguration);
        $host = $this->resolveHost($databaseConfiguration);
        $port = $this->resolvePort($databaseConfiguration);

        if ($this->verify($database, $databaseConfiguration)) {
            $this->processExecutor->execute($this->getKillConnectionsCommand(
                $user,
                $host,
                $port,
                $databaseConfiguration->getDbName()
            ));
            $this->drop($databaseConfiguration->getDbName(), $databaseConfiguration);
            $this->processExecutor->execute(
                $this->getDumpCommand($user, $host, $port, $database, $databaseConfiguration->getDbName())
            );
        } else {
            throw new \Exception(sprintf('Verification failed for "%s"', $databaseConfiguration->getDbName()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function drop($name, DatabaseConfigurationInterface $databaseConfiguration)
    {
        if ($databaseConfiguration->getPassword()) {
            putenv(sprintf('PGPASSWORD=%s', $databaseConfiguration->getPassword()));
        }

        $user = $this->resolveUser($databaseConfiguration);
        $host = $this->resolveHost($databaseConfiguration);
        $port = $this->resolvePort($databaseConfiguration);

        $this->processExecutor->execute($this->getDropDatabaseCommand($user, $host, $port, $name));
    }

    /**
     * {@inheritdoc}
     */
    public function verify($name, DatabaseConfigurationInterface $databaseConfiguration)
    {
        if ($databaseConfiguration->getPassword()) {
            putenv(sprintf('PGPASSWORD=%s', $databaseConfiguration->getPassword()));
        }

        $user = $this->resolveUser($databaseConfiguration);
        $host = $this->resolveHost($databaseConfiguration);
        $port = $this->resolvePort($databaseConfiguration);

        try {
            $this->processExecutor->execute($this->getVerifyDatabaseCommand($user, $host, $port, $name));

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedOs()
    {
        return [
            AbstractDatabaseIsolator::OS_WINDOWS,
            AbstractDatabaseIsolator::OS_LINUX,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedDrivers()
    {
        return [
            DatabaseConfigurationInterface::DRIVER_PDO_POSTGRESQL,
        ];
    }

    /**
     * @param string $user
     * @param string $host
     * @param int $port
     * @param string $database
     *
     * @return string
     */
    protected function getVerifyDatabaseCommand($user, $host, $port, $database)
    {
        return sprintf(
            '%s -U %s -h %s -p %d -d %s -c "SELECT 1;"',
            $this->psqlBin,
            $user,
            $host,
            $port,
            $database
        );
    }

    /**
     * @param string $user
     * @param string $host
     * @param int $port
     * @param string $database
     *
     * @return string
     */
    protected function getKillConnectionsCommand($user, $host, $port, $database)
    {
        $killQuery = sprintf(
            'SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = \'%s\';',
            $database
        );

        return sprintf(
            '%s -U %s -h %s -p %d template1 -t -c "%s"',
            $this->psqlBin,
            $user,
            $host,
            $port,
            $killQuery
        );
    }

    /**
     * @param string $user
     * @param string $host
     * @param int $port
     * @param string $database
     *
     * @return string
     */
    protected function getDropDatabaseCommand($user, $host, $port, $database)
    {
        return sprintf(
            '%s --if-exists -U %s -h %s -p %d %s',
            $this->dropdbBin,
            $user,
            $host,
            $port,
            $database
        );
    }

    /**
     * @param string $user
     * @param string $host
     * @param int $port
     * @param string $databaseFrom
     * @param string $databaseTo
     *
     * @return string
     */
    protected function getDumpCommand($user, $host, $port, $databaseFrom, $databaseTo)
    {
        return sprintf(
            '%s -U %s -h %s -p %d -O %s -T %s %s',
            $this->createdbBin,
            $user,
            $host,
            $port,
            $user,
            $databaseFrom,
            $databaseTo
        );
    }

    /**
     * @param DatabaseConfigurationInterface $databaseConfiguration
     *
     * @return string
     */
    protected function resolveUser(DatabaseConfigurationInterface $databaseConfiguration)
    {
        return $databaseConfiguration->getUser() ?: 'postgres';
    }

    /**
     * @param DatabaseConfigurationInterface $databaseConfiguration
     *
     * @return string
     */
    protected function resolveHost(DatabaseConfigurationInterface $databaseConfiguration)
    {
        return $databaseConfiguration->getHost() ?: '127.0.0.1';
    }

    /**
     * @param DatabaseConfigurationInterface $databaseConfiguration
     *
     * @return int
     */
    protected function resolvePort(DatabaseConfigurationInterface $databaseConfiguration)
    {
        return $databaseConfiguration->getPort() ?: 5432;
    }
}
