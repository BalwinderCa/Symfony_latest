<?php
// src/AppBundle/Form/GifType.php

namespace App\AppBundle\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GifType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Add basic fields
        $builder->add('title', TextType::class, [
            'label' => 'Title'
        ]);
        $builder->add('description', null, [
            'label' => 'Description'
        ]);
        $builder->add('enabled', CheckboxType::class, [
            'label' => 'Enabled',
            'required' => false
        ]);
        $builder->add('comment', CheckboxType::class, [
            'label' => 'Enable comments',
            'required' => false
        ]);
        $builder->add('tags', null, [
            'label' => 'Tags (Keywords)'
        ]);

        // Categories and languages as entities
        $builder->add('categories', EntityType::class, [
            'class' => 'App\AppBundle\Entity\Category',
            'expanded' => true,
            'multiple' => true,
            'by_reference' => false
        ]);
        $builder->add('languages', EntityType::class, [
            'class' => 'App\AppBundle\Entity\Language',
            'expanded' => true,
            'multiple' => true,
            'by_reference' => false
        ]);

        // Handle filegif conditionally
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $gif = $event->getData();
            $form = $event->getForm();

            if ($gif && $gif->getId() !== null) {
                $form->add('filegif', FileType::class, [
                    'label' => '',
                    'required' => false
                ]);
            } else {
                $form->add('filegif', FileType::class, [
                    'label' => '',
                    'required' => true
                ]);
            }
        });

        // Save button
        $builder->add('save', SubmitType::class, [
            'label' => 'Save'
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'Gif';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure form options if necessary
        ]);
    }
}
