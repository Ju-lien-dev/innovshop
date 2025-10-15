<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'required' => false,
                'label' => 'Prénom',
                'constraints' => [new Assert\Length(max: 100)],
            ])
            ->add('nom', TextType::class, [
                'required' => false,
                'label' => 'Nom',
                'constraints' => [new Assert\Length(max: 100)],
            ])
            ->add('phone', TelType::class, [
                'required' => false,
                'label' => 'Téléphone',
                'constraints' => [new Assert\Length(max: 30)],
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(message: 'L’email est requis.'),
                    new Assert\Email(message: 'Email invalide.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
