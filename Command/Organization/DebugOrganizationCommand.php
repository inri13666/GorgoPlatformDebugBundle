<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Organization;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\DBALException;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;

class DebugOrganizationCommand extends ContainerAwareCommand
{
    const DATE_FORMAT = 'Y-m-d h:i:sA';

    /** @var OrganizationRepository */
    protected $organizationRepository;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('gorgo:debug:organization')
            ->addOption('organization', null, InputOption::VALUE_OPTIONAL, "organization id or name")
            ->setDescription('Displays Organization\'s debug information');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $organization = $input->getOption('organization');
        if ($organization) {
            $organizationObject = $this->findOrganization($organization);
            if ($organizationObject) {
                $this->processOrganization($organizationObject, $output);
            } else {
                $output->writeln(sprintf('Organization "%s" not found', $organization));
            }

            return;
        }

        $this->processOrganizations($output);
    }

    protected function processOrganizations($output)
    {
        $table = new Table($output);
        $table->setHeaders([
            'Id',
            'Name',
            'Created',
        ])->setRows([]);
        $organizations = $this->getOrganizationRepository()->findAll();
        /** @var Organization $organization */
        foreach ($organizations as $organization) {
            $table->addRow([
                $organization->getId(),
                $organization->getName(),
                $organization->getCreatedAt() ? $organization->getCreatedAt()->format(self::DATE_FORMAT) : 'N/A',
            ]);
        }
        $table->render();
    }

    /**
     * @param string|int $organization
     *
     * @return null|Organization
     */
    private function findOrganization($organization)
    {
        $repo = $this->getOrganizationRepository();

        try {
            $result = $repo->getOrganizationById($organization);
            if ($result) {
                return $result;
            }
        } catch (DBALException $e) {
        }

        try {
            $result = $repo->getOrganizationByName($organization);
            if ($result) {
                return $result;
            }
        } catch (DBALException $e) {
        }

        return null;
    }

    /**
     * @return ObjectRepository|OrganizationRepository
     */
    private function getOrganizationRepository()
    {
        if (!$this->organizationRepository) {
            $this->organizationRepository = $this->getContainer()->get('doctrine')->getRepository(Organization::class);
        }

        return $this->organizationRepository;
    }

    /**
     * @param Organization $organizationObject
     * @param OutputInterface $output
     */
    private function processOrganization(Organization $organizationObject, OutputInterface $output)
    {
        $buCount = $organizationObject->getBusinessUnits()->count();
        $table = new Table($output);
        $table->setHeaders([
            'Index',
            'Value',
        ])->setRows([]);
        $table->addRow(['Id', $organizationObject->getId()]);
        $table->addRow(['Name', $organizationObject->getName()]);
        $table->addRow([
            'Created',
            $organizationObject->getCreatedAt()
                ? $organizationObject->getCreatedAt()->format(self::DATE_FORMAT) : 'N/A',
        ]);
        $table->addRow(['Description', $organizationObject->getDescription()]);
        $table->addRow(['BusinessUnits Count', $buCount]);
        $table->addRow(['Users Count', $organizationObject->getUsers()->count()]);

        $table->render();

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE && $buCount) {
            $this->processBusinessUnits($organizationObject, $output);
        }
    }

    /**
     * @param Organization $organizationObject
     * @param OutputInterface $output
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function processBusinessUnits(Organization $organizationObject, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders([
            'Id',
            'Name',
            'Email',
            'Fax',
            'Phone',
            'Users Count',
            'Created',
            'Updated',
        ])->setRows([]);
        /** @var BusinessUnit $businessUnit */
        foreach ($organizationObject->getBusinessUnits() as $businessUnit) {
            $table->addRow([
                $businessUnit->getId(),
                $businessUnit->getName(),
                $businessUnit->getEmail() ?: 'N/A',
                $businessUnit->getFax() ?: 'N/A',
                $businessUnit->getPhone() ?: 'N/A',
                $businessUnit->getUsers()->count() ?: 'N/A',
                $businessUnit->getCreatedAt() ? $businessUnit->getCreatedAt()->format(self::DATE_FORMAT) : 'N/A',
                $businessUnit->getUpdatedAt() ? $businessUnit->getUpdatedAt()->format(self::DATE_FORMAT) : 'N/A',
            ]);
        }

        $table->render();
    }
}
