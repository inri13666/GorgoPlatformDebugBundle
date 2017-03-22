<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Translation;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Intl\Intl;

class AddLanguageCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('gorgo:platform:language:add')
            ->addArgument('code', InputArgument::REQUIRED, "Language Code")
            ->addOption('enable', null, InputOption::VALUE_NONE, "Determines if new language should be enabled")
            ->setDescription('Adds new language');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManagerForClass(Language::class);

        $code = $input->getArgument('code');

        if (!in_array($code, Intl::getLocaleBundle()->getLocales(), true)) {
            $output->writeln(sprintf('Unsupported code "%s"', $code));

            return 1;
        }

        if ($em->getRepository(Language::class)->findOneBy(['code' => $code])) {
            $output->writeln(sprintf('Language with code "%s" already exists', $code));

            return 1;
        };

        $language = new Language();
        $language->setCode($code);

        if ($input->getOption('enable')) {
            $language->setEnabled(true);
        }

        $em->persist($language);
        $em->flush();

        $output->writeln(sprintf('Language with code "%s" added successfully', $code));
    }
}
