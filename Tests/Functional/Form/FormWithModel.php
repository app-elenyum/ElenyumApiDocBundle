<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests\Functional\Form;

use Elenyum\ApiDocBundle\Annotation\Model;
use Elenyum\ApiDocBundle\Tests\Functional\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormWithModel extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quz', TextType::class, ['documentation' => ['ref' => new Model(['type' => User::class])]]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }
}
