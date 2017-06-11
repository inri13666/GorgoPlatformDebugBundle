<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Entity;

use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Util\ArrayConverter;
use Symfony\Component\Yaml\Yaml;

class DumpEntityTranslationsCommand extends ContainerAwareCommand
{
    const NAME = 'gorgo:entity:translations:dump';
    const INLINE_LEVEL = 10;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Dump translations for entity')
            ->addArgument(
                'entity',
                InputArgument::REQUIRED,
                'Entity class name whose translations should to be dumped, like "Oro/Bundle/TaskBundle/Entity/Task"'
            )
            ->addOption(
                'locale',
                null,
                InputOption::VALUE_OPTIONAL,
                'Locale whose translations should to be dumped',
                Translator::DEFAULT_LOCALE
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $entityClass = $input->getArgument('entity');
        $fieldsProvider = $this->getContainer()->get('oro_entity.entity_field_provider');
        $entity = $this->getContainer()->get('oro_entity.entity_provider')->getEntity($entityClass, false);
        $translationKeys = [$entity['label'], $entity['plural_label']];

        $translationKeys = array_merge(
            $translationKeys,
            array_map(
                function ($field) {
                    return $field['label'];
                },
                $fieldsProvider->getFields($entityClass, false, false, false, false, true, false)
            )
        );

        $translations = $this->processKeys(
            $this->getContainer()->get('translator.default'),
            $translationKeys,
            $input->getOption('locale')
        );

        $output->write(Yaml::dump(ArrayConverter::expandToTree($translations), self::INLINE_LEVEL));
    }

    /**
     * @param Translator $translator
     * @param array $keys
     * @param string|null $locale
     *
     * @return array
     */
    protected function processKeys(Translator $translator, array $keys, $locale)
    {
        $translations = [];
        foreach ($keys as $key) {
            if ($translator->hasTrans($key, null, $locale)) {
                $translation = $translator->trans($key, [], null, $locale);
            } elseif ($translator->hasTrans($key, null, Translator::DEFAULT_LOCALE)) {
                $translation = $translator->trans($key, [], null, Translator::DEFAULT_LOCALE);
            } else {
                $translation = '';
            }

            $translations[$key] = $translation;
        }

        return $translations;
    }
}
