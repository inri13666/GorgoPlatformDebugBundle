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
        return false;
    }

    /**
     * @inheritDoc
     */
    public function onPostReceived(Context $context)
    {
        return null;
    }

}
