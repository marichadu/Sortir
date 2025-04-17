<?php

namespace App\Form;

use App\Entity\Campus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'required' => false,
                'placeholder' => 'Tous les campus',
                'label' => 'Campus',
                'attr' => ['class' => 'form-select']
            ])
            ->add('search', TextType::class, [
                'required' => false,
                'label' => 'Le nom de la sortie contient',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Rechercher...'
                ]
            ])
            ->add('dateDebut', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Entre',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateFin', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'et',
                'attr' => ['class' => 'form-control']
            ])
            ->add('isOrganisateur', CheckboxType::class, [
                'required' => false,
                'label' => 'Sorties dont je suis l\'organisateur/trice',
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('isInscrit', CheckboxType::class, [
                'required' => false,
                'label' => 'Sorties auxquelles je suis inscrit/e',
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('isNotInscrit', CheckboxType::class, [
                'required' => false,
                'label' => 'Sorties auxquelles je ne suis pas inscrit/e',
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('isPassed', CheckboxType::class, [
                'required' => false,
                'label' => 'Sorties passées',
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
} 