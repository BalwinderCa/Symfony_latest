<?php
// src/AppBundle/Form/CategoryType.php

namespace App\AppBundle\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Add the title field
        $builder->add('title', null, [
            'label' => 'Category Title'
        ]);

        // Handle the conditional logic for the file field
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $category = $event->getData();
            $form = $event->getForm();

            // Check if the category has an ID to determine whether it's an edit or a new category
            if ($category && $category->getId() !== null) {
                $form->add('file', FileType::class, [
                    'label' => '',
                    'required' => false
                ]);
            } else {
                $form->add('file', FileType::class, [
                    'label' => '',
                    'required' => true
                ]);
            }
        });

        // Add the save button
        $builder->add('save', SubmitType::class, [
            'label' => 'SAVE Category'
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'Category';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here, e.g., the data class
        ]);
    }
}
