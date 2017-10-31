<?php

namespace Gorgo\Bundle\PlatformDebugBundle\MessageQueue\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ContainerResetExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class DisableContainerResetExtension extends ContainerResetExtension
{
    /**
     * {@inheritDoc}
     */
    public function onPreReceived(Context $context)
    {
        return;
    }
}
