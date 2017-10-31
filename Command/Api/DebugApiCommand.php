<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Api;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Oro\Bundle\ApiBundle\ApiDoc\RestRequestTypeProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugApiCommand extends Command
{
    const NAME = 'gorgo:debug:api';

    /** @var ApiDocExtractor */
    protected $apiDocExtractor;

    /**
     * {@inheritDoc}
     */
    public function __construct($name, ApiDocExtractor $apiDocExtractor = null)
    {
        parent::__construct($name);
        $this->apiDocExtractor = $apiDocExtractor;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->addArgument('view', InputArgument::OPTIONAL, '', RestRequestTypeProvider::JSON_API_VIEW);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $view = $input->getArgument('view');
        $extractedDoc = $this->apiDocExtractor->all($view);
        var_dump(reset($extractedDoc));
    }
}
