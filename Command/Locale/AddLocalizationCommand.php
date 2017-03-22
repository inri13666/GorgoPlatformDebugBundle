<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Locale;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Intl\Intl;

class AddLocalizationCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('gorgo:platform:localization:add')
            ->addOption('code', null, InputOption::VALUE_REQUIRED, "Language Code")
            ->addOption('name', null, InputOption::VALUE_REQUIRED, "Localization Name")
            ->addOption('formatting', null, InputOption::VALUE_OPTIONAL, "Formatting Code")
            ->addOption('title', null, InputOption::VALUE_OPTIONAL, "Localization Title")
            ->addOption('parent', null, InputOption::VALUE_OPTIONAL, "Parent Localization Name")
            ->setDescription('Adds new localization');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getEm();

        $requiredOptions = [
            'code',
            'name',
        ];
        foreach ($requiredOptions as $option) {
            if (!$input->getOption($option)) {
                $output->writeln(sprintf('option "%s" is required', $option));

                return -1;
            }
        }
        $code = $input->getOption('code');
        $formatting = $input->getOption('formatting') ?: $input->getOption('code');
        $name = $input->getOption('name');
        $title = $input->getOption('title') ?: $name;

        if ($em->getRepository(Localization::class)->findOneBy(['name' => $name])) {
            $output->writeln(sprintf('Localization with name "%s" already exists', $name));

            return 1;
        };

        $language = $em->getRepository(Language::class)->findOneBy(['code' => $code]);
        if ((!$language instanceof Language) || (!$language->isEnabled())) {
            $output->writeln(sprintf('Language with code "%s" not found or not enabled', $code));

            return 1;
        };

        $this->createLocalization($language, $name, $formatting, $title);

        $output->writeln(sprintf('Localization with name "%s" added successfully', $name));
    }

    /**
     * @param Language $language
     * @param string $name
     * @param string $formatting
     * @param string $title
     */
    protected function createLocalization(Language $language, $name, $formatting, $title)
    {
        $localization = new Localization();
        $localization->setLanguage($language)
            ->setName($name)
            ->setFormattingCode($formatting)
            ->setDefaultTitle($title);

        $this->getEm()->persist($localization);
        $this->getEm()->flush();
    }

    /**
     * @return EntityManager|null
     */
    private function getEm()
    {
        return $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManagerForClass(Localization::class);
    }
}
