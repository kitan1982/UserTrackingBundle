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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TrackingTabType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'user',
            'userpicker',
            array(
                'label' => 'user',
                'required' => false,
                'picker_name' => 'tracking_user'
            )
        );
        $builder->add(
            'group',
            'entity',
            array(
                'label' => 'group',
                'class' => 'ClarolineCoreBundle:Group',
                'choice_translation_domain' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('g')
                        ->orderBy('g.name', 'ASC');
                },
                'property' => 'name',
                'multiple' => false,
                'required' => false
            )
        );
        $builder->add(
            'role',
            'entity',
            array(
                'label' => 'role',
                'class' => 'ClarolineCoreBundle:Role',
                'choice_translation_domain' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('r')
                        ->where('r.workspace IS NULL')
                        ->andWhere('r.type = 1')
                        ->andWhere('r.name != :anonymousRole')
                        ->setParameter('anonymousRole', 'ROLE_ANONYMOUS')
                        ->orderBy('r.translationKey', 'ASC');
                },
                'property' => 'translationKey',
                'multiple' => false,
                'required' => false
            )
        );
    }

    public function getName()
    {
        return 'tracking_tab_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('translation_domain' => 'platform'));
    }
}
