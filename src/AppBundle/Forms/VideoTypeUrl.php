<?php
// src/AppBundle/Form/VideoTypeUrl.php

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

class VideoTypeUrl extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, [
            'label' => 'Title',
        ])
        ->add('description', TextType::class, [
            'label' => 'Description',
            'required' => false,
        ])
        ->add('enabled', CheckboxType::class, [
            'label' => 'Enabled',
            'required' => false,
        ])
        ->add('comment', TextType::class, [
            'label' => 'Enabled comments',
            'required' => false,
        ])
        ->add('tags', TextType::class, [
            'label' => 'Tags (Keywords)',
            'required' => false,
        ])
        ->add('urlvideo', TextType::class, [
            'label' => 'Video URL',
            'required' => false,
        ])
        ->add('categories', EntityType::class, [
            'class' => 'App\AppBundle\Entity\Category',
            'expanded' => true,
            'multiple' => true,
            'by_reference' => false,
        ])
        ->add('languages', EntityType::class, [
            'class' => 'App\AppBundle\Entity\Language',
            'expanded' => true,
            'multiple' => true,
            'by_reference' => false,
        ])
        ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $article = $event->getData();
            $form = $event->getForm();

            if ($article && null !== $article->getId()) {
                $form->add('file', FileType::class, [
                    'label' => '',
                    'required' => false,
                ]);
            } else {
                $form->add('file', FileType::class, [
                    'label' => '',
                    'required' => true,
                ]);
            }
        })
        ->add('save', SubmitType::class, [
            'label' => 'Save',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'Video';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Add any default options if necessary
        ]);
    }
}
