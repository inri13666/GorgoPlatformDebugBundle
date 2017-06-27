<?php

namespace Gorgo\Bundle\PlatformDebugBundle\DependencyInjection\CompilerPass;

use Gorgo\Bundle\PlatformDebugBundle\MessageQueue\Command\ConsumeCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MessageQueueDebugPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->replaceClass($container);
        $this->loggerCollect($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function replaceClass(ContainerBuilder $container)
    {
        if ($container->hasDefinition('oro_message_queue.client.consume_messages_command')) {
            $definition = $container->getDefinition('oro_message_queue.client.consume_messages_command');
            $definition->setClass(ConsumeCommand::class);
            $definition->addMethodCall('setExtension', [new Reference('gorgo.message_queue.extension.debug_extension')]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loggerCollect(ContainerBuilder $container)
    {
        $channels = ['app'];
        if ($container->hasDefinition('gorgo.message_queue.extension.debug_extension')) {
            $extension = $container->getDefinition('gorgo.message_queue.extension.debug_extension');

            foreach ($container->findTaggedServiceIds('monolog.logger') as $id => $tags) {
                foreach ($tags as $tag) {
                    if (empty($tag['channel'])) {
                        continue;
                    }

                    $resolvedChannel = $container->getParameterBag()->resolveValue($tag['channel']);
                    $channels[] = sprintf('monolog.logger.%s', $resolvedChannel);
                }
            }

            foreach (array_unique($channels) as $chanel) {
                if ($container->hasDefinition($chanel)) {
                    $extension->addMethodCall('addLogger', [new Reference($chanel)]);
                }
            }
        }
    }
}
