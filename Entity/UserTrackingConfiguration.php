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

use Claroline\CoreBundle\Entity\Widget\Widget;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="claro_user_tracking_configuration")
 * @ORM\Entity(repositoryClass="Claroline\UserTrackingBundle\Repository\UserTrackingConfigurationRepository")
 */
class UserTrackingConfiguration
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="Claroline\CoreBundle\Entity\Widget\Widget"
     * )
     * @ORM\JoinTable(name="claro_user_tracking_widgets")
     */
    protected $widgets;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->widgets = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getWidgets()
    {
        return $this->widgets->toArray();
    }

    public function getWidgetsArrayCollection()
    {
        return $this->widgets;
    }

    public function addWidget(Widget $widget)
    {
        if (!$this->widgets->contains($widget)) {
            $this->widgets->add($widget);
        }

        return $this;
    }

    public function removeWidget(Widget $widget)
    {
        if ($this->users->contains($widget)) {
            $this->users->removeElement($widget);
        }

        return $this;
    }
}
