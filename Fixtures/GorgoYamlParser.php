<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Fixtures;

use Nelmio\Alice\Fixtures\Loader;
use Nelmio\Alice\Fixtures\Parser\Methods\Yaml;
use Symfony\Component\HttpKernel\KernelInterface;

class GorgoYamlParser extends Yaml
{
    /** @var KernelInterface */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    protected function processIncludes($data, $filename)
    {
        $includes = isset($data['include']) ? $data['include'] : [];
        unset($data['include']);

        if ($includes) {
            foreach ($includes as $include) {
                if (isset($include[0]) && '@' === $include[0] && $this->kernel) {
                    $include = str_replace(':', '/Tests/Behat/Features/Fixtures/', $include);
                    $includeFile = $this->kernel->locateResource($include, null, true);
                } else {
                    $includeFile = dirname($filename) . DIRECTORY_SEPARATOR . $include;
                }

                $includeData = $this->parse($includeFile);

                $data = $this->mergeIncludeData($data, $includeData);
            }
        }

        return $data;
    }

    /**
     * @param Loader $context
     */
    public function setContext(Loader $context)
    {
        $this->context = $context;
    }
}
