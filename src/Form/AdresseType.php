<?php

namespace App\Form;

use App\Entity\Adresse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdresseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, [
                'label' => 'Type d’adresse (ex : Domicile, Bureau...)',
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom complet',
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Adresse::class,
        ]);
    }
}
