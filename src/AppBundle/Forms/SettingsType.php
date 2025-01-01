<?php
// src/AppBundle/Form/SettingsType.php

namespace App\AppBundle\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Basic text fields
        $builder->add('firebasekey', TextType::class, [
            'label' => 'Firebase Key',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('currency', TextType::class, [
            'label' => 'Currency',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);

        // Checkbox fields
        $builder->add('adduser', TextType::class, [
            'label' => 'Add User',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('sharevideo', TextType::class, [
            'label' => 'Share Video',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('viewvideo', TextType::class, [
            'label' => 'View Video',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('addvideo', TextType::class, [
            'label' => 'Add Video',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('shareimage', TextType::class, [
            'label' => 'Share Image',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('viewimage', TextType::class, [
            'label' => 'View Image',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('addimage', TextType::class, [
            'label' => 'Add Image',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('sharegif', TextType::class, [
            'label' => 'Share GIF',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('viewgif', TextType::class, [
            'label' => 'View GIF',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('addgif', TextType::class, [
            'label' => 'Add GIF',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('sharequote', TextType::class, [
            'label' => 'Share Quote',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('viewquote', TextType::class, [
            'label' => 'View Quote',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('addquote', TextType::class, [
            'label' => 'Add Quote',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        
        // Number fields
        $builder->add('minpoints', TextType::class, [
            'label' => 'Minimum Points',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);
        $builder->add('oneusdtopoints', TextType::class, [
            'label' => '1 USD to Points',
            'required' => false,
            'data' => false,  // Default value (set to false if not checked)
        ]);

        // Submit button
        $builder->add('save', SubmitType::class, [
            'label' => 'Save'
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'Settings';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure options if necessary
        ]);
    }
}
