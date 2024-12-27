<?php
// src/AppBundle/Form/LanguageType.php

namespace App\AppBundle\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Add basic fields
        $builder->add('language', TextType::class, [
            'label' => 'Language title'
        ]);
        $builder->add('enabled', CheckboxType::class, [
            'label' => 'Enabled',
            'required' => false
        ]);

        // Handle file field conditionally
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $language = $event->getData();
            $form = $event->getForm();

            if ($language && $language->getId() !== null) {
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

        // Save button
        $builder->add('save', SubmitType::class, [
            'label' => 'Save the Language'
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'Language';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure form options if necessary
        ]);
    }
}
