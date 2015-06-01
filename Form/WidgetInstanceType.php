<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\UserTrackingBundle\Form;

use Claroline\UserTrackingBundle\Entity\UserTrackingConfiguration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class WidgetInstanceType extends AbstractType
{
    private $config;

    public function __construct(UserTrackingConfiguration $config)
    {
        $this->config = $config;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array('constraints' => new NotBlank()));
        $builder->add(
            'widget',
            'entity',
            array(
                'class' => 'Claroline\CoreBundle\Entity\Widget\Widget',
                'choice_translation_domain' => true,
                'expanded' => false,
                'multiple' => false,
                'choices' => $this->config->getWidgets()
            )
        );
    }

    public function getName()
    {
        return 'widget_instance_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('translation_domain' => 'widget'));
    }
}
