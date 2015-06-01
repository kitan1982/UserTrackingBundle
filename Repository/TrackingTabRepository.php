<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\UserTrackingBundle\Repository;

use Claroline\CoreBundle\Entity\Home\HomeTab;
use Claroline\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class TrackingTabRepository extends EntityRepository
{
    public function findTrackingTabsByUser(User $user, $executeQuery = true)
    {
        $dql = '
            SELECT tt
            FROM Claroline\UserTrackingBundle\Entity\TrackingTab tt
            JOIN tt.homeTab ht
            WHERE tt.owner = :user
            AND EXISTS (
                SELECT htc
                FROM Claroline\CoreBundle\Entity\Home\HomeTabConfig
                WHERE htc.homeTab = ht
            )
            ORDER BY htc.tabOrder ASC
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('user', $user);

        return $executeQuery ? $query->getResult() : $query;
    }

    public function findTrackingTabByUserAndHomeTab(
        User $user,
        HomeTab $homeTab,
        $executeQuery = true
    )
    {
        $dql = '
            SELECT tt
            FROM Claroline\UserTrackingBundle\Entity\TrackingTab tt
            WHERE tt.owner = :user
            AND tt.homeTab = :homeTab
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('user', $user);
        $query->setParameter('homeTab', $homeTab);

        return $executeQuery ? $query->getOneOrNullResult() : $query;
    }
}
