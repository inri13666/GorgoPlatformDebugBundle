<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Locale;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugLocalizationCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('gorgo:debug:localization')
            ->setDescription('Dumps the list of Localizations');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $localizations = $this->getContainer()->get('oro_locale.manager.localization')->getLocalizations();

        $table = new Table($output);
        $table->addRow([])->setHeaders([
            'ID',
            'Name',
            'Title',
            'Lang code',
            'Formatting code',
        ]);
        foreach ($localizations as $localization) {
            $table->addRow([
                $localization->getId(),
                $localization->getName(),
                $localization->getTitle()->getString(),
                $localization->getLanguageCode(),
                $localization->getFormattingCode(),
            ]);
        }
        $table->render();
        $output->writeln('');
    }
}
