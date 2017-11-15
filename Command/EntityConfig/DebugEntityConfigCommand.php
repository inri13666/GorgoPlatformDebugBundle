<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\EntityConfig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugEntityConfigCommand extends Command
{
    const NAME = 'gorgo:debug:entity-config';

    /** @var ManagerRegistry */
    protected $registry;

    protected $htmlTagHelper;

    /**
     * {@inheritDoc}
     */
    public function __construct($name, ManagerRegistry $registry)
    {
        parent::__construct(self::NAME);
        $this->registry = $registry;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled()
    {
        return true;
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
        $entityClass = $input->getArgument('entity');
        $criteria = [];
        if ($entityClass) {
            $criteria = ['className' => $entityClass];
        }
        $em = $this->registry
            ->getManagerForClass(EntityConfigModel::class);
        $data = $em
            ->getRepository(EntityConfigModel::class)
            ->findBy($criteria);

        if (count($data)) {
            $this->processEntities($output, [reset($data)]);
            $em->flush();
        } else {
            $output->writeln('Nothing found');
        }
    }

    /**
     * @param OutputInterface $output
     * @param array|EntityConfigModel[] $data
     */
    protected function processEntities(OutputInterface $output, array $data)
    {
        $table = new Table($output);
        $table->setHeaders([
            '#',
            'Class',
            'Fields Count',
        ])->setRows([]);

        $i = 1;
        /** @var EntityConfigModel $item */
        foreach ($data as $item) {
            $table->addRow([$i++, $item->getClassName(), count($item->getFields())]);
            $item->fromArray(
                'entity',
                $this->processArrayData($item->toArray('entity'), ['label', 'plural_label', 'description'])
            );
            $fields = $item->getFields();
            if (!empty($fields)) {
                foreach ($fields as $fieldConfig) {
                    $fieldConfigData = $fieldConfig->toArray('entity');
                    if (!empty($fieldConfigData)) {
                        $fieldConfig->fromArray(
                            'entity',
                            $this->processArrayData($fieldConfigData, ['label', 'description'])
                        );
                    }
                }
            }
        }
        $table->render();
    }

    /**
     * @param array $input
     * @param array $keys
     *
     * @return array
     */
    protected function processArrayData(array $input, array $keys)
    {
        foreach ($keys as $key) {
            if (!empty($input[$key])) {
                $input[$key] = $input[$key] . '::' . '<script>alert()</script>';
            }
        }

        return $input;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
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
