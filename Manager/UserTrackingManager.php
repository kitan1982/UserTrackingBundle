<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\UserTrackingBundle\Manager;

use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\UserTrackingBundle\Entity\UserTrackingConfiguration;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.manager.user_tracking_manager")
 */
class UserTrackingManager
{
    private $om;
    private $userTrackingConfigRepo;

    /**
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
        $this->userTrackingConfigRepo =
            $om->getRepository('ClarolineUserTrackingBundle:UserTrackingConfiguration');
    }

    public function persistConfiguration(UserTrackingConfiguration $config)
    {
        $this->om->persist($config);
        $this->om->flush();
    }

    public function getConfiguration()
    {
        $configs = $this->userTrackingConfigRepo->findAll();

        if (count($configs) > 0) {
            $config = $configs[0];
        } else {
            $config = new UserTrackingConfiguration();
            $this->persistConfiguration($config);
        }

        return $config;
    }
}
