<?php
// src/AppBundle/Form/QuoteReviewType.php

namespace App\AppBundle\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class QuoteReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Basic form fields
        $builder->add('color', CheckboxType::class, [
            'label' => 'Enabled',
            'required' => false
        ]);
        $builder->add('comment', TextType::class, [
            'label' => 'Enabled comments',
            'required' => false
        ]);
        $builder->add('tags', TextType::class, [
            'label' => 'Tags (Keywords)',
            'required' => false
        ]);

        // Add categories entity field
        $builder->add('categories', EntityType::class, [
            'class' => 'App\AppBundle\Entity\Category',
            'expanded' => true,
            'multiple' => true,
            'by_reference' => false,
            'label' => 'Categories'
        ]);

        // Add languages entity field
        $builder->add('languages', EntityType::class, [
            'class' => 'App\AppBundle\Entity\Language',
            'expanded' => true,
            'multiple' => true,
            'by_reference' => false,
            'label' => 'Languages'
        ]);

        // Add save button
        $builder->add('save', SubmitType::class, [
            'label' => 'Save'
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'QuoteReview';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure options if necessary
        ]);
    }
}
