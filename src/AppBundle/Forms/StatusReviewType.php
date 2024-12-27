<?php
// src/AppBundle/Form/StatusReviewType.php

namespace App\AppBundle\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatusReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, [
            'label' => 'Title',
        ])
        ->add('description', TextareaType::class, [
            'label' => 'Description',
            'required' => false,
        ])
        ->add('comment', TextareaType::class, [
            'label' => 'Comment',
            'required' => false,
        ])
        ->add('tags', TextType::class, [
            'label' => 'Tags',
            'required' => false,
        ])
        ->add('categories', EntityType::class, [
            'class' => 'App\AppBundle\Entity\Category',
            'label' => 'Categories',
            'expanded' => true,
            'multiple' => true,
            'by_reference' => false,
        ])
        ->add('languages', EntityType::class, [
            'class' => 'App\AppBundle\Entity\Language',
            'label' => 'Languages',
            'expanded' => true,
            'multiple' => true,
            'by_reference' => false,
        ])
        ->add('save', SubmitType::class, [
            'label' => 'Review',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'Status';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Add any default options if necessary
        ]);
    }
}
