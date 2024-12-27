<?php

namespace App\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\UserBundle\Entity\User;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full name',
            ])
            ->add('facebook', TextType::class, [
                'label' => 'Facebook account',
                'required' => false,
            ])
            ->add('twitter', TextType::class, [
                'label' => 'Twitter account',
                'required' => false,
            ])
            ->add('instagram', TextType::class, [
                'label' => 'Instagram account',
                'required' => false,
            ])
            ->add('emailo', TextType::class, [
                'label' => 'E-email',
                'required' => false,
            ])
            ->add('type', TextType::class, [
                'label' => 'Account type',
                'attr' => ['readonly' => true],
            ])
            ->add('email', TextType::class, [
                'label' => 'E-mail or AuthId',
                'attr' => ['readonly' => true],
            ])
            ->add('trusted', CheckboxType::class, [
                'required' => false,
            ])
            ->add('enabled', CheckboxType::class, [
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'SAVE USER',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'task_item',
        ]);
    }
}
