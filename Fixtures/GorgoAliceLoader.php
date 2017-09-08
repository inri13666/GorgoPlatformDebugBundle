<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Fixtures;

use Nelmio\Alice\Fixtures\Loader as AliceLoader;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Nelmio\Alice\Persister\Doctrine as AliceDoctrine;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GorgoAliceLoader extends AliceLoader
{
    /**
     * @return AliceCollection
     */
    public function getReferenceRepository()
    {
        return $this->objects;
    }

    /**
     * @param RegistryInterface $doctrine
     */
    public function setDoctrine(RegistryInterface $doctrine)
    {
        $this->typeHintChecker->setPersister(new AliceDoctrine($doctrine->getManager()));
    }
}
