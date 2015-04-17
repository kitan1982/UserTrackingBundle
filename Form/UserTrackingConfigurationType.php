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

use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserTrackingConfigurationType extends AbstractType
{
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'widgets',
            'entity',
            array(
                'label' => $this->translator->trans('widgets', array(), 'platform'),
                'class' => 'ClarolineCoreBundle:Widget\Widget',
                'query_builder' => function (EntityRepository $er) {

                    return $er->createQueryBuilder('w')
                        ->where('w.isDisplayableInDesktop = true')
                        ->orWhere('w.isDisplayableInDesktop = false AND w.isDisplayableInWorkspace = false')
                        ->orderBy('w.name', 'ASC');
                },
                'property' => 'name',
                'expanded' => true,
                'multiple' => true,
                'required' => false
            )
        );
    }

    public function getName()
    {
        return 'user_tracking_configuration_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'translation_domain' => 'widget'
            )
        );
    }
}
