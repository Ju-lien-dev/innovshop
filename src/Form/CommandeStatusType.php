<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CommandeStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('status', ChoiceType::class, [
            'choices' => [
                'Payée'        => 'paid',
                'En préparation' => 'processing',
                'Expédiée'     => 'shipped',
                'Livrée'       => 'delivered',
                'Remboursée'   => 'refunded',
                'Annulée'      => 'cancelled',
            ],
            'label' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
            'method'     => 'POST',
        ]);
    }
}
