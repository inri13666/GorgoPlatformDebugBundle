<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Email;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class EmailTemplatesImportCommand extends ContainerAwareCommand
{
    protected $adminUser;

    protected $organization;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:email:template:import')
            ->addArgument('source', InputArgument::REQUIRED, "Folder or File to import")
            ->addOption('force', null, InputOption::VALUE_NONE, "Force update")
            ->setDescription('Imports email templates');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $this->getContainer()
            ->get('kernel')
            ->locateResource($input->getArgument('source'));

        $templates = $this->getRawTemplates($source);
        $output->writeln(sprintf('Found %d templates', count($templates)));
        $manager = $this->getEmailTemplatesManager();

        foreach ($templates as $fileName => $file) {
            $template = file_get_contents($file['path']);
            $parsedTemplate = EmailTemplate::parseContent($template);
            $templateName = isset($parsedTemplate['params']['name']) ? $parsedTemplate['params']['name'] : $fileName;
            $existingTemplate = $this->findExistingTemplate($templateName);

            if ($existingTemplate) {
                if ($input->getOption('force')) {
                    $output->writeln(sprintf('"%s" updated', $existingTemplate->getName()));
                    $this->updateExistingTemplate($existingTemplate, $parsedTemplate);
                } else {
                    $output->writeln(sprintf('"%s" updates skipped', $existingTemplate->getName()));
                }
            } else {
                $this->loadNewTemplate($fileName, $file);
            }
        }

        $manager->flush();
    }

    /**
     * @param $name
     *
     * @return null|EmailTemplate
     */
    private function findExistingTemplate($name)
    {
        return $this->getEmailTemplateRepository()->findByName($name);
    }

    /**
     * @return ObjectRepository|EmailTemplateRepository
     */
    private function getEmailTemplateRepository()
    {
        return $this->getDoctrine()->getRepository(EmailTemplate::class);
    }

    /**
     * @param $source
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    private function getRawTemplates($source)
    {
        if (is_dir($source)) {
            $finder = new Finder();
            $sources = $finder->files()->in($source);
        } else {
            $sources[] = new SplFileInfo($source, null, null);
        }

        $templates = [];
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($sources as $source) {
            $fileName = str_replace(array('.html.twig', '.html', '.txt.twig', '.txt'), '', $source->getFilename());

            $format = 'html';
            if (preg_match('#\.(html|txt)(\.twig)?#', $source->getFilename(), $match)) {
                $format = $match[1];
            }

            $templates[$fileName] = array(
                'path' => $source->getPath() . DIRECTORY_SEPARATOR . $source->getFilename(),
                'format' => $format,
            );
        }

        return $templates;
    }

    /**
     * @return EntityManager
     */
    private function getEmailTemplatesManager()
    {
        return $this->getDoctrine()->getManagerForClass(EmailTemplate::class);
    }

    /**
     * @param string $fileName
     * @param array $file
     */
    protected function loadNewTemplate($fileName, $file)
    {
        $template = file_get_contents($file['path']);
        $emailTemplate = new EmailTemplate($fileName, $template, $file['format']);
        $emailTemplate->setOwner($this->getAdminUser());
        $emailTemplate->setOrganization($this->getOrganization());
        $this->getEmailTemplatesManager()->persist($emailTemplate);
    }

    /**
     * @param EmailTemplate $emailTemplate
     * @param array $template
     */
    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $template)
    {
        $emailTemplate->setContent($template['content']);
        foreach ($template['params'] as $param => $value) {
            $setter = sprintf('set%s', ucfirst($param));
            $emailTemplate->$setter($value);
        }
    }

    /**
     * @return Organization
     */
    protected function getOrganization()
    {
        if ($this->organization) {
            return $this->organization;
        }

        $this->organization = $this->getDoctrine()->getRepository('OroOrganizationBundle:Organization')->getFirst();

        return $this->organization;
    }

    /**
     * Get administrator user
     *
     * @return User
     *
     * @throws \RuntimeException
     */
    protected function getAdminUser()
    {
        if ($this->adminUser) {
            return $this->adminUser;
        }

        /** @var RoleRepository $repository */
        $repository = $this->getDoctrine()->getRepository('OroUserBundle:Role');
        /** @var Role $role */
        $role = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

        if (!$role) {
            throw new \RuntimeException('Administrator role should exist.');
        }

        $user = $repository->getFirstMatchedUser($role);

        if (!$user) {
            throw new \RuntimeException(
                'Administrator user should exist to load email templates.'
            );
        }

        $this->adminUser = $user;

        return $this->adminUser;
    }

    /**
     * @return Registry|object
     */
    private function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }
}
