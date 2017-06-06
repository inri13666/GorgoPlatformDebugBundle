<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Service\Database;

use Gorgo\Bundle\PlatformDebugBundle\Exception\Database\IsolatorNotFoundException;
use Gorgo\Bundle\PlatformDebugBundle\Isolator\Database\DatabaseIsolatorInterface;
use Gorgo\Bundle\PlatformDebugBundle\Model\Database\DatabaseConfigurationInterface;

class IsolatorRegistry
{
    const SERVICE_TAG = 'akuma.database.isolator';

    /** @var array|DatabaseIsolatorInterface[] */
    protected $isolators = [];

    /**
     * @param DatabaseIsolatorInterface $databaseIsolator
     * @param string $alias
     */
    public function addIsolator(DatabaseIsolatorInterface $databaseIsolator, $alias)
    {
        $this->isolators[$alias] = $databaseIsolator;
    }

    /**
     * @param DatabaseConfigurationInterface $configuration
     *
     * @return DatabaseIsolatorInterface
     */
    public function findIsolator(DatabaseConfigurationInterface $configuration)
    {
        foreach ($this->isolators as $isolator) {
            if ($isolator->isConfigurationSupported($configuration)) {
                return $isolator;
            }
        }

        throw new IsolatorNotFoundException();
    }
}
