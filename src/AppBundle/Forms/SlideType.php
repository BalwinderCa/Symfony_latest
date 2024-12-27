<?php
// src/AppBundle/Form/SlideType.php

namespace App\AppBundle\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\AppBundle\Entity\Status;

class SlideType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, [
            'label' => 'Title',
        ])
        ->add('url', UrlType::class, [
            'label' => 'URL',
            'required' => false,
        ])
        ->add('category', null, [
            'label' => 'Category',
        ])
        ->add('status', EntityType::class, [
            'class' => Status::class,
            'choice_label' => 'title',  // Change 'name' to 'title'
            'placeholder' => 'Choose a status',
            'required' => false,
        ])
        ->add('type', ChoiceType::class, [
            'label' => 'Type',
            'choices' => [
                'Status' => 3,
                'Category' => 1,
                'Url' => 2,
            ],
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $slide = $event->getData();
            $form = $event->getForm();
            if ($slide && null !== $slide->getId()) {
                $form->add('file', null, [
                    'label' => 'File',
                    'required' => false,
                ]);
            } else {
                $form->add('file', null, [
                    'label' => 'File',
                    'required' => true,
                ]);
            }
        });

        $builder->add('save', SubmitType::class, [
            'label' => 'Save',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'Slide';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure options if necessary
        ]);
    }
}
