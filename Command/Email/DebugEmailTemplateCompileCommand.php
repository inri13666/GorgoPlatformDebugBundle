<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Email;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class DebugEmailTemplateCompileCommand extends ContainerAwareCommand
{
    /**
     * {@internaldoc}
     */
    protected function configure()
    {
        $this->setName('oro:debug:email:template:compile')
            ->setDescription('Renders given email template')
            ->addOption(
                'template',
                null,
                InputOption::VALUE_OPTIONAL,
                'The name of email template to be compiled.'
            )
            ->addOption(
                'params-file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to YML file with required params for compilation.'
            )
            ->addOption(
                'entity-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'An entity ID.'
            )
            ->addOption(
                'recipient',
                null,
                InputOption::VALUE_OPTIONAL,
                'Recipient email address. [Default: null]',
                null
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $templateName = $input->getOption('template');
        $template = $this->getRepository()->findByName($templateName);
        if (!$template) {
            $output->writeln(sprintf('Template "%s" not found', $templateName));

            return 1;
        }
        $params = $this->getNormalizedParams($input->getOption('params-file'));

        if ($template->getEntityName()) {
            $params['entity'] = $this->getEntity($template->getEntityName(), $input->getOption('entity-id'));
        }

        $subject = $this->getEmailRenderer()->renderWithDefaultFilters($template->getSubject(), $params);
        $body = $this->getEmailRenderer()->renderWithDefaultFilters($template->getContent(), $params);

        if (!$input->getOption('recipient')) {
            $output->writeln(sprintf('SUBJECT: %s', $subject));
            $output->writeln('');
            $output->writeln('BODY:');
            $output->writeln($body);
        } else {
            $emailMessage = new \Swift_Message(
                $subject,
                $body,
                $template->getType() === 'html' ? 'text/html' : null
            );

            $emailMessage->setTo($input->getOption('recipient'));

            if (!$this->getMailer()->send($emailMessage)) {
                $output->writeln(sprintf('Unable send email to "%s"', $input->getOption('recipient')));

                return 1;
            } else {
                $output->writeln(sprintf('Message successfully send to "%s"', $input->getOption('recipient')));
            }
        }

        return 0;
    }

    /**
     * @return ObjectRepository|EmailTemplateRepository
     */
    private function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(EmailTemplate::class);
    }

    /**
     * @return object|DirectMailer
     */
    private function getMailer()
    {
        return $this->getContainer()->get('oro_email.direct_mailer');
    }

    /**
     * @return object|EmailRenderer
     */
    private function getEmailRenderer()
    {
        return $this->getContainer()->get('oro_email.email_renderer');
    }

    /**
     * @param string $paramsFile
     *
     * @return array
     */
    private function getNormalizedParams($paramsFile)
    {
        if (is_file($paramsFile) && is_readable($paramsFile)) {
            return Yaml::parse(file_get_contents($paramsFile));
        }

        return [];
    }

    /**
     * @param string $entityClass
     * @param null|mixed $entityId
     *
     * @return object
     */
    private function getEntity($entityClass, $entityId = null)
    {
        /** @var DoctrineHelper $dh */
        $dh = $this->getContainer()->get('oro_entity.doctrine_helper');
        $entity = $dh->createEntityInstance($entityClass);
        if ($entityId) {
            $entity = $dh->getEntity($entityClass, $entityId) ?: $entity;
        }

        return $entity;
    }
}
