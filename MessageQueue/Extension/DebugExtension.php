<?php

namespace Gorgo\Bundle\PlatformDebugBundle\MessageQueue\Extension;

use Gorgo\Bundle\PlatformDebugBundle\MessageQueue\Log\ProxyHandler;
use Monolog\Logger;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Psr\Log\LoggerInterface;

class DebugExtension extends AbstractExtension
{
    /** @var LoggerInterface|Logger */
    protected $loggers = [];

    /** @var array */
    protected $excludedLoggerChains = [];

    /**
     * {@inheritdoc}
     */
    public function addLogger(LoggerInterface $logger)
    {
        $this->loggers[] = $logger;
    }

    /**
     * @param array $loggerChain
     */
    public function setExcludedLoggerChain(array $loggerChain)
    {
        $this->excludedLoggerChains = $loggerChain;
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        $debugHandler = new ProxyHandler();
        $debugHandler->setLogger($context->getLogger());
        foreach ($this->loggers as $logger) {
            if (!$logger instanceof Logger || in_array($logger->getName(), $this->excludedLoggerChains)) {
                continue;
            }

            $logger->setHandlers(
                array_merge([$debugHandler], $logger->getHandlers())
            );
        }
    }
}
