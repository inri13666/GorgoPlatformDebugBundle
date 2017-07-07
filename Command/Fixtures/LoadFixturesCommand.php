<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Fixtures;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Nelmio\Alice\Fixtures\Loader as AliceLoader;

class LoadFixturesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('gorgo:fixtures:load')
            ->addArgument('fixture', InputArgument::REQUIRED)
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, '', 'behat')
            ->setDescription('Loads Alice fixtures from Behat');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fixture = str_replace(':', '/Tests/Behat/Features/Fixtures/', $input->getArgument('fixture'));
        if ('@' === $fixture[0]) {
            $fixture = $this->getContainer()->get('kernel')->locateResource($fixture, null, true);
        }
        $loader = new AliceLoader();
        $loader->setLogger(new ConsoleLogger($output));
        $loader->addParser($this->getContainer()->get('gorgo.fixtures.yml_parser'));
        $loader->setReferences($this->configureBaseReferences()->toArray());
        $data = $loader->load($fixture);
        foreach ($data as $item) {
            $this->getEntityManager()->persist($item);
        }
        $this->getEntityManager()->flush();
    }

    protected function configureBaseReferences()
    {
        $references = new AliceCollection();
        $user = $this->getDefaultUser();
        $userRole = $this->getRole(User::ROLE_DEFAULT);
        $adminRole = $this->getRole(User::ROLE_ADMINISTRATOR);

        $references->set('admin', $user);
        $references->set('userRole', $userRole);
        $references->set('adminRole', $adminRole);
        $references->set('organization', $user->getOrganization());
        $references->set('business_unit', $user->getOwner());
        $references->set('defaultProductFamily', $this->getDefaultProductFamily());
        $references->set(
            'en_language',
            $this->getEntityManager()->getRepository(Language::class)->findOneBy(['code' => 'en'])
        );

        return $references;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return User
     * @throws \InvalidArgumentException
     */
    protected function getDefaultUser()
    {
        /** @var RoleRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroUserBundle:Role');

        /** @var Role $role */
        $role = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

        if (!$role) {
            throw new \InvalidArgumentException('Administrator role should exist.');
        }

        $user = $repository->getFirstMatchedUser($role);

        if (!$user) {
            throw new \InvalidArgumentException(
                'Administrator user should exist.'
            );
        }

        return $user;
    }

    /**
     * @return AttributeFamily
     * @throws \InvalidArgumentException
     */
    protected function getDefaultProductFamily()
    {
        $repository = $this->getEntityManager()->getRepository(AttributeFamily::class);
        $attributeFamily = $repository->findOneBy([
            'code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE,
        ]);

        if (!$attributeFamily) {
            throw new \InvalidArgumentException('Default product attribute family should exist.');
        }

        return $attributeFamily;
    }

    public function getRole($role)
    {
        return $this->getEntityManager()->getRepository(Role::class)->findOneBy(['role' => $role]);
    }
}
