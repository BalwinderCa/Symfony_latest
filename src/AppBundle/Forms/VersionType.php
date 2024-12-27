<?php
// src/AppBundle/Form/VersionType.php

namespace App\AppBundle\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class VersionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, [
            'label' => 'Version title',
        ])
        ->add('features', TextType::class, [
            'label' => 'The new features',
            'required' => false,
        ])
        ->add('code', TextType::class, [
            'label' => 'Version code',
        ])
        ->add('enabled', CheckboxType::class, [
            'label' => 'Enabled',
            'required' => false,
        ])
        ->add('save', SubmitType::class, [
            'label' => 'Save',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'Version';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Add any default options if necessary
        ]);
    }
}
