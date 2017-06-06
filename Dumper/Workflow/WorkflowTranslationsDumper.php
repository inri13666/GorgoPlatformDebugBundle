<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Dumper\Workflow;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowTranslationsDumper
{
    /**
     * @param WorkflowDefinition $definition
     * @param null $locale
     *
     * @return mixed
     */
    public function dump(WorkflowDefinition $definition, $locale = null)
    {
        return [
            'method' => __METHOD__,
            'name' => $definition->getName(),
            'locale' => $locale,
        ];
    }
}
