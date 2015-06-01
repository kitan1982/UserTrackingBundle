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

use Claroline\CoreBundle\Entity\Home\HomeTab;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\UserTrackingBundle\Entity\TrackingTab;
use Claroline\UserTrackingBundle\Entity\UserTrackingConfiguration;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.manager.user_tracking_manager")
 */
class UserTrackingManager
{
    private $om;
    private $trackingTabRepo;
    private $userTrackingConfigRepo;

    /**
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
        $this->trackingTabRepo =
            $om->getRepository('ClarolineUserTrackingBundle:TrackingTab');
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

    public function persistTrackingTab(TrackingTab $trackingTab)
    {
        $this->om->persist($trackingTab);
        $this->om->flush();
    }

    public function getUserTrackingTabByHomeTab(User $user, HomeTab $homeTab)
    {
        $trackingTab = $this->getTrackingTabByUserAndHomeTab($user, $homeTab);

        if (is_null($trackingTab)) {
            $trackingTab = new TrackingTab();
            $trackingTab->setOwner($user);
            $trackingTab->setHomeTab($homeTab);
            $this->persistTrackingTab($trackingTab);
        }

        return $trackingTab;
    }


    /*******************************************
     * Access to TrackingTabRepository methods *
     *******************************************/

    public function getTrackingTabsByUser(User $user, $executeQuery = true)
    {
        return $this->trackingTabRepo->findTrackingTabsByUser($user, $executeQuery);
    }

    public function getTrackingTabByUserAndHomeTab(
        User $user,
        HomeTab $homeTab,
        $executeQuery = true
    )
    {
        return $this->trackingTabRepo->findTrackingTabByUserAndHomeTab(
            $user,
            $homeTab,
            $executeQuery
        );
    }
}
