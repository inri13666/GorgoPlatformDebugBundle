<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Isolator\Database;

use Gorgo\Bundle\PlatformDebugBundle\Model\Database\DatabaseConfigurationInterface;

abstract class AbstractDatabaseIsolator implements DatabaseIsolatorInterface
{
    const SALT = 'backup';

    const OS_WINDOWS = 'WINDOWS';
    const OS_LINUX = 'LINUX';
    const OS_MAC = 'MAC';
    const OS_UNKNOWN = 'UNKNOWN';

    /**
     * @return array
     */
    abstract public function getSupportedOs();

    /**
     * @return array
     */
    abstract public function getSupportedDrivers();

    /**
     * @return string
     */
    protected function getCurrentOs()
    {
        switch (PHP_OS) {
            case 'WINNT':
                return self::OS_WINDOWS;
                break;
            case 'Linux':
                return self::OS_LINUX;
                break;
            default:
                return self::OS_UNKNOWN;
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isConfigurationSupported(DatabaseConfigurationInterface $databaseConfiguration)
    {
        return $databaseConfiguration->isValid() &&
        in_array($databaseConfiguration->getDriver(), $this->getSupportedDrivers(), true) &&
        in_array($this->getCurrentOs(), $this->getSupportedOs(), true);
    }

    /**
     * @inheritDoc
     */
    public function getBackupDbName($id, DatabaseConfigurationInterface $databaseConfiguration)
    {
        return sprintf('%s_%s_%s', self::SALT, $databaseConfiguration->getDbName(), $id);
    }
}
