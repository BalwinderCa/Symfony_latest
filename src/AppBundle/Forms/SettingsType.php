<?php
namespace App\AppBundle\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\AppBundle\Entity\Settings;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firebasekey', TextType::class, [
                'label' => 'Firebase Key',
                'required' => false
            ])
            ->add('currency', TextType::class, [
                'label' => 'Currency',
                'required' => false
            ])
            ->add('minpoints', TextType::class, [
                'label' => 'Minimum Points',
                'required' => false
            ])
            ->add('oneusdtopoints', TextType::class, [
                'label' => '1 USD to Points',
                'required' => false
            ])
            ->add('adduser', TextType::class, [
                'label' => 'Add User',
                'required' => false
            ])
            ->add('sharevideo', TextType::class, [
                'label' => 'Share Video',
                'required' => false
            ])
            ->add('viewvideo', TextType::class, [
                'label' => 'View Video',
                'required' => false
            ])
            ->add('addvideo', TextType::class, [
                'label' => 'Add Video',
                'required' => false
            ])
            ->add('shareimage', TextType::class, [
                'label' => 'Share Image',
                'required' => false
            ])
            ->add('viewimage', TextType::class, [
                'label' => 'View Image',
                'required' => false
            ])
            ->add('addimage', TextType::class, [
                'label' => 'Add Image',
                'required' => false
            ])
            ->add('sharegif', TextType::class, [
                'label' => 'Share GIF',
                'required' => false
            ])
            ->add('viewgif', TextType::class, [
                'label' => 'View GIF',
                'required' => false
            ])
            ->add('addgif', TextType::class, [
                'label' => 'Add GIF',
                'required' => false
            ])
            ->add('sharequote', TextType::class, [
                'label' => 'Share Quote',
                'required' => false
            ])
            ->add('viewquote', TextType::class, [
                'label' => 'View Quote',
                'required' => false
            ])
            ->add('addquote', TextType::class, [
                'label' => 'Add Quote',
                'required' => false
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Settings::class
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'Settings';
    }
}