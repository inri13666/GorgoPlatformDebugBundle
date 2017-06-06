<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Workflow;

use Faker\Factory;
use Oro\Bundle\TaskBundle\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('oro:workflow:test')->setDescription('Execute transition of workflow');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $faker = Factory::create();

        $output->writeln('start');
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $taskPriority = $doctrine->getRepository('OroTaskBundle:TaskPriority')
            ->findOneBy(['name' => 'low']);
        $organization = $doctrine->getRepository('OroOrganizationBundle:Organization')
            ->findOneBy([]);
        $admin = $doctrine->getRepository('OroUserBundle:User')
            ->findOneBy(['username' => 'admin']);

        for ($j = 0; $j < 1; $j++) {
            for ($i = 0; $i < 100; $i++) {
                $task = new Task();

                $task->setSubject($faker->word);
                $task->setDescription($faker->sentence);
                $task->setCreatedAt(new \DateTime());
                $task->setUpdatedAt(new \DateTime());
                $task->setDueDate($faker->dateTimeBetween('now', '+2 years'));
                $task->setTaskPriority($taskPriority);
                $task->setOrganization($organization);
                $task->setOwner($admin);

                $em->persist($task);
            }
            $output->writeln('start flush ' . ($j+1)*100);
            $em->flush();
            $output->writeln('end flush', $j);
            $em->clear();
        }

        $output->writeln('end');
    }
}
