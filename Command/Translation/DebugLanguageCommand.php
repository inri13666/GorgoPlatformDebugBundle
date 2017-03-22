<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Translation;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Intl\Intl;

use Oro\Bundle\TranslationBundle\Translation\Translator;

class DebugLanguageCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('gorgo:debug:language')
            ->setDescription('Dumps the list of installed languages');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $languages = $this->getContainer()->get('oro_translation.provider.language')->getLanguages();

        $table = new Table($output);
        $table->addRow([])->setHeaders([
            'ID',
            'Code',
            'Title',
            'Status',
        ]);
        foreach ($languages as $language) {
            $table->addRow([
                $language->getId(),
                $language->getCode(),
                Intl::getLocaleBundle()->getLocaleName($language->getCode(), Translator::DEFAULT_LOCALE) . '',
                $language->isEnabled() ? 'Enabled' : 'Disabled',
            ]);
        }
        $table->render();
        $output->writeln('');
    }
}
