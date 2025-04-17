<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VilleFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'required' => false,
                'label' => 'Rechercher',
                'attr' => [
                    'placeholder' => 'Nom de la ville...',
                    'class' => 'form-control'
                ]
            ])
            ->add('codePostal', TextType::class, [
                'required' => false,
                'label' => 'Code Postal',
                'attr' => [
                    'placeholder' => 'Code postal...',
                    'class' => 'form-control'
                ]
            ])
            ->add('departement', TextType::class, [
                'required' => false,
                'label' => 'Département',
                'attr' => [
                    'placeholder' => 'Département...',
                    'class' => 'form-control'
                ]
            ])
            ->add('region', TextType::class, [
                'required' => false,
                'label' => 'Région',
                'attr' => [
                    'placeholder' => 'Région...',
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
} 