<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Command\Fixtures;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Nelmio\Alice\Persister\Doctrine;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
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
        $loader->setPersister(new Doctrine($this->getEntityManager(), true));
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

        //From \Oro\Bundle\ApplicationBundle\Tests\Behat\ReferenceRepositoryInitializer
        /** @var EntityRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroAddressBundle:Country');
        /** @var Country $germany */
        $germany = $repository->findOneBy(['name' => 'Germany']);
        $references->set('germany', $germany);
        /** @var Country $us */
        $us = $repository->findOneBy(['name' => 'United States']);
        $references->set('united_states', $us);

        /** @var RegionRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroAddressBundle:Region');
        /** @var Region $berlin */
        $berlin = $repository->findOneBy(['name' => 'Berlin']);
        $references->set('berlin', $berlin);

        /** @var CustomerUserRoleRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroCustomerBundle:CustomerUserRole');
        /** @var CustomerUserRole buyer */
        $buyer = $repository->findOneBy(['role' => 'ROLE_FRONTEND_BUYER']);
        $references->set('buyer', $buyer);

        /** @var CustomerUserRole $administrator */
        $administrator = $repository->findOneBy(['role' => 'ROLE_FRONTEND_ADMINISTRATOR']);
        $references->set('front_admin', $administrator);

        /** @var ProductUnitRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroProductBundle:ProductUnit');
        /** @var ProductUnit $item */
        $item = $repository->findOneBy(['code' => 'item']);
        $references->set('item', $item);
        /** @var ProductUnit $each */
        $each = $repository->findOneBy(['code' => 'each']);
        $references->set('each', $each);
        /** @var ProductUnit $set */
        $set = $repository->findOneBy(['code' => 'set']);
        $references->set('set', $set);

        /** @var AddressTypeRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroAddressBundle:AddressType');
        /** @var AddressType $billingType */
        $billingType = $repository->findOneBy(['name' => 'billing']);
        $references->set('billingType', $billingType);
        /** @var AddressType $shippingType */
        $shippingType = $repository->findOneBy(['name' => 'shipping']);
        $references->set('shippingType', $shippingType);

        /** @var EntityRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroPricingBundle:PriceListCurrency');
        /** @var PriceListCurrency EUR */
        $eur = $repository->findOneBy(['currency' => 'EUR']);
        $references->set('eur', $eur);

        /** @var PriceListRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroPricingBundle:PriceList');
        /** @var PriceList $pricelist1 */
        $pricelist1 = $repository->findOneBy(['id' => '1']);
        $references->set('defaultPriceList', $pricelist1);

        /** @var WebsiteRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroWebsiteBundle:Website');
        /** @var Website $website1 */
        $website1 = $repository->findOneBy(['id' => '1']);
        $references->set('website1', $website1);

        /** @var CombinedPriceListRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroPricingBundle:CombinedPriceList');
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $repository->findOneBy(['id' => '1']);
        $references->set('combinedPriceList', $combinedPriceList);

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        $enumInventoryStatuses = $this->getEntityManager()
            ->getRepository($inventoryStatusClassName)
            ->findOneBy(['id' => 'in_stock']);
        $references->set('enumInventoryStatuses', $enumInventoryStatuses);

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
