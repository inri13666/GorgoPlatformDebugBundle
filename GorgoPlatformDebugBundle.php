<?php

namespace Gorgo\Bundle\PlatformDebugBundle;

use Gorgo\Bundle\PlatformDebugBundle\DependencyInjection\CompilerPass\MessageQueueDebugPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GorgoPlatformDebugBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new MessageQueueDebugPass());
    }
}
