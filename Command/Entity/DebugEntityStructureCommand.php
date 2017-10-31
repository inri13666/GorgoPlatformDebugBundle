<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Entity;

use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugEntityStructureCommand extends Command
{
    const NAME = 'oro:debug:entity-structure';

    /** @var EntityStructureDataProvider|null */
    protected $entityStructureDataProvider = null;

    /**
     * @inheritDoc
     */
    public function __construct($name, $entityStructureDataProvider = null)
    {
        parent::__construct(self::NAME);
        if ($entityStructureDataProvider instanceof EntityStructureDataProvider) {
            $this->entityStructureDataProvider = $entityStructureDataProvider;
        }
    }

    /**
     * @inheritDoc
     */
    public function isEnabled()
    {
        return $this->entityStructureDataProvider instanceof EntityStructureDataProvider;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->addArgument('entity', InputArgument::OPTIONAL);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = $this->entityStructureDataProvider->getData();
        $entity = $input->getArgument('entity');
        if (null === $entity) {
            $this->processEntities($output, $data);

            return 0;
        } else {
            foreach ($data as $item) {
                if (in_array($entity, [$item->getClassName(), $item->getAlias(), $item->getPluralAlias()], false)) {
                    $this->processEntity($output, $item);

                    return 0;
                }
            }

            $output->writeln('Nothing found');
        }
    }

    /**
     * @param OutputInterface $output
     * @param array|EntityStructure[] $data
     */
    protected function processEntities(OutputInterface $output, array $data)
    {
        $table = new Table($output);
        $table->setHeaders([
            '#',
            'Class',
            'Alias',
            'Plural Alias',
            'Fields Count',
        ])->setRows([]);

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $table->setHeaders([
                '#',
                'Class',
                'Label',
                'Plural label',
                'Icon',
                'Alias',
                'Plural Alias',
                'Fields Count',
                'Options',
            ])->setRows([]);
        }
        $i = 1;
        foreach ($data as $item) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $table->addRow([
                    $i++,
                    $item->getClassName(),
                    $item->getLabel(),
                    $item->getPluralLabel(),
                    $item->getIcon(),
                    $item->getAlias(),
                    $item->getPluralAlias(),
                    count($item->getFields()),
                    $this->optionsToString($item->getOptions()),
                ]);
            } else {
                $table->addRow([
                    $i++,
                    $item->getClassName(),
                    $item->getAlias(),
                    $item->getPluralAlias(),
                    count($item->getFields()),
                ]);
            }
        }
        $table->render();
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
    }

    private function processEntity(OutputInterface $output, EntityStructure $item)
    {
        $table = new Table($output);
        $table->setHeaders([])->setRows([]);
        $table->addRows([
            ['Class Name', $item->getClassName()],
            ['Label', $item->getLabel()],
            ['Plural Label', $item->getPluralLabel()],
            ['Alias', $item->getAlias()],
            ['Plural Alias', $item->getPluralAlias()],
            ['Options', $this->optionsToString($item->getOptions()),],
        ]);
        $table->render();

        $table = new Table($output);
        $table->setHeaders([
            'Name',
            //'Label',
            'Type',
            'RelationType',
            'RelatedEntityName',
            //'Options',
        ])->setRows([]);
        $fields = $item->getFields();
        foreach ($fields as $field) {
            $table->addRow([
                $field->getName(),
                //$field->getLabel(),
                $field->getType(),
                $field->getRelationType(),
                $field->getRelatedEntityName(),
                //$this->optionsToString($field->getOptions()),
            ]);
        }
        $table->render();
    }

    /**
     * @param array $options
     *
     * @return string
     */
    protected function optionsToString(array $options)
    {
        return implode(' ,', array_keys(array_filter($options, function ($value) {
            return (bool)$value;
        })));
    }
}
