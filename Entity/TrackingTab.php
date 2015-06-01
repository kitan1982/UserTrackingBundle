<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\UserTrackingBundle\Entity;

use Claroline\CoreBundle\Entity\Group;
use Claroline\CoreBundle\Entity\Home\HomeTab;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Claroline\UserTrackingBundle\Repository\TrackingTabRepository")
 * @ORM\Table(name="claro_user_tracking_tab")
 */
class TrackingTab
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\User"
     * )
     * @ORM\JoinColumn(name="owner_id", nullable=false, onDelete="CASCADE")
     */
    protected $owner;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\Home\HomeTab"
     * )
     * @ORM\JoinColumn(name="home_tab_id", nullable=false, onDelete="CASCADE")
     */
    protected $homeTab;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\User"
     * )
     * @ORM\JoinColumn(name="user_id", nullable=true, onDelete="SET NULL")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\Group"
     * )
     * @ORM\JoinColumn(name="group_id", nullable=true, onDelete="SET NULL")
     */
    protected $group;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\Role"
     * )
     * @ORM\JoinColumn(name="role_id", nullable=true, onDelete="SET NULL")
     */
    protected $role;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner(User $owner)
    {
        $this->owner = $owner;
    }

    public function getHomeTab()
    {
        return $this->homeTab;
    }

    public function setHomeTab(HomeTab $homeTab)
    {
        $this->homeTab = $homeTab;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user = null)
    {
        $this->user = $user;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup(Group $group = null)
    {
        $this->group = $group;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole(Role $role = null)
    {
        $this->role = $role;
    }
}
