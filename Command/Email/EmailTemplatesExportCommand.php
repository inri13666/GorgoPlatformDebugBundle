<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Email;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;

class EmailTemplatesExportCommand extends ContainerAwareCommand
{
    protected $adminUser;

    protected $organization;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:email:template:export')
            ->addArgument('dest', InputArgument::REQUIRED, "Folder to export")
            ->addOption('template', null, InputOption::VALUE_OPTIONAL, "template name")
            ->setDescription('Imports email templates');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $destination = $input->getArgument('dest');
        try {
            $destination = $this->getContainer()->get('kernel')->locateResource($destination);
        } catch (\InvalidArgumentException $e) {
        }

        if (!is_dir($destination) || !is_writable($destination)) {
            $output->writeln(sprintf('<error>Destination path "%s" should be writable folder</error>', $destination));

            return 1;
        }

        $templates = $this->getEmailTemplates($input->getOption('template'));
        $output->writeln(sprintf('Found %d templates for export', count($templates)));

        /** @var EmailTemplate $template */
        foreach ($templates as $template) {
            $content = sprintf(
                "@name = %s\n@entityName = %s\n@subject = %s\n@isSystem = %d\n@isEditable = %d\n\n%s",
                $template->getName(),
                $template->getEntityName(),
                $template->getSubject(),
                $template->getIsSystem(),
                $template->getIsEditable(),
                $template->getContent()
            );

            $filename = sprintf(
                "%s.%s.twig",
                preg_replace('/[^a-z0-9\._-]+/i', '', $template->getName()),
                $template->getType() ?: 'html'
            );

            file_put_contents(
                $destination . DIRECTORY_SEPARATOR . $filename,
                $content
            );
        }
    }

    /**
     * @return EmailTemplate[]
     */
    private function getEmailTemplates($templateName = null)
    {
        $criterion = [];
        if ($templateName) {
            $criterion = ['name' => $templateName];
        }

        return $this->getDoctrine()->getRepository(EmailTemplate::class)->findBy($criterion);
    }

    /**
     * @return Registry|object
     */
    private function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }
}
