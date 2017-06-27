<?php

namespace Gorgo\Bundle\PlatformDebugBundle\MessageQueue\Log;

use Monolog\Handler\AbstractProcessingHandler;
use Psr\Log\LoggerInterface;

class ProxyHandler extends AbstractProcessingHandler
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $this->logger->log(
            strtolower($record['level_name']),
            $record['formatted'],
            $record['context']
        );
    }
}
