<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Workflow;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartWorkflowCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('gorgo:test:workflow:start')
            ->addArgument('workflow-name', InputArgument::REQUIRED, "Workflow Name")
            ->setDescription('Starts workflow for all related entities');
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled()
    {
        return $this->getApplication()->getKernel()->isDebug();
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Workflow $workflow */
        $workflow = $this->getContainer()->get('oro_workflow.registry.system')->getWorkflow(
            $input->getArgument('workflow-name')
        );

        if (!$workflow) {
            //TODO: Add info
            return -1;
        }
        if (!$workflow->isActive()) {
            //TODO: Add info
            return -1;
        }
        if (!$workflow->getTransitionManager()->getStartTransitions()->count()) {
            //TODO: Add info
            return -1;
        }

        $entityClass = $workflow->getDefinition()->getRelatedEntity();
        $workflowManager = $this->getContainer()->get('oro_workflow.registry.workflow_manager')->getManager();
        $entities = $this
            ->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass($entityClass)
            ->findAll();

        $startTransitions = $workflow->getTransitionManager()->getStartTransitions()->toArray();

        foreach ($entities as $entity) {
            if (!$workflowManager->hasWorkflowItemsByEntity($entity)) {
                $startTransition = $startTransitions[array_rand($startTransitions)];
                $workflowManager->startWorkflow(
                    $workflow->getName(),
                    $entity,
                    $startTransition
                );
            }
        }
    }
}
