<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\MessageQueue;

use Gorgo\Bundle\PlatformDebugBundle\Async\DebugTopics;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeadlockMQCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('gorgo:message-queue:deadlock:test')
            ->addOption('count', null, InputOption::VALUE_REQUIRED);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = $input->getOption('count');
        $producer = $this->getContainer()->get('oro_message_queue.client.message_producer');

        while($count--) {
            $in = 3 * rand(1, 5);
            $rand = rand(0, 1);
            $message = new Message(['id1' => $rand ? $in - 1 : $in + 1 , 'id2' => $rand ? $in + 1 : $in - 1]);
            $message->setPriority(MessagePriority::HIGH);
            $producer->send(DebugTopics::DEADLOCK_TOPIC, $message);
        }
    }
}
