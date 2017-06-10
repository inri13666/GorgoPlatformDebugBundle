<?php
namespace Gorgo\Bundle\PlatformDebugBundle\MessageProcessor;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class PositiveMessageProcessor implements MessageProcessorInterface
{
    /**
     * @inheritDoc
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        return self::ACK;
    }
}
