<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Dumper\Workflow;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowConfigurationDumper
{
    /**
     * @param WorkflowDefinition $definition
     *
     * @return mixed
     */
    public function dump(WorkflowDefinition $definition)
    {
        return [
            'method' => __METHOD__,
            'name' => $definition->getName(),
        ];
    }
}
