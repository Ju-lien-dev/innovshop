<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as T;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\ShippingMethod;

final class ShippingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $options): void
    {
        $b
            ->add('full_name', T\TextType::class, [
                'label' => 'Nom complet',
                'constraints' => [new Assert\NotBlank(message: 'Votre nom est requis.')],
            ])
            ->add('address', T\TextType::class, [
                'label' => 'Adresse',
                'constraints' => [new Assert\NotBlank(message: 'Votre adresse est requise.')],
            ])
            ->add('zip', T\TextType::class, [
                'label' => 'Code postal',
                'constraints' => [new Assert\NotBlank(message: 'Votre code postal est requis.')],
            ])
            ->add('city', T\TextType::class, [
                'label' => 'Ville',
                'constraints' => [new Assert\NotBlank(message: 'Votre ville est requise.')],
            ])
            ->add('country', T\CountryType::class, [
                'label' => 'Pays',
                'data'  => 'FR',
            ])
            ->add('delivery', EntityType::class, [
                'class' => ShippingMethod::class,
                'query_builder' => fn(\App\Repository\ShippingMethodRepository $r) =>
                $r->createQueryBuilder('s')
                    ->andWhere('s.isActive = :a')->setParameter('a', true)
                    ->orderBy('s.amountCents', 'ASC'),
                'choice_label' => fn(ShippingMethod $m) => sprintf(
                    '%s — %s € • %d–%d j',
                    $m->getName(),
                    number_format($m->getAmountCents() / 100, 2, ',', ' '),
                    $m->getMinDays(),
                    $m->getMaxDays()
                ),
                'expanded' => true,
                'multiple' => false,
                'label'    => 'Mode de livraison',
                'label_html' => true,
                'choice_attr' => function (ShippingMethod $m) {
                    return [
                        'data-amount' => $m->getAmountCents(),                 // centimes
                        'data-name'   => $m->getName(),
                        'data-days'   => sprintf('%d–%d', $m->getMinDays(), $m->getMaxDays()),
                    ];
                },
            ])
            ->add('notes', T\TextareaType::class, [
                'label'    => 'Instructions (facultatif)',
                'required' => false,
            ])
            ->add('accept', T\CheckboxType::class, [
                'label' => "J'accepte les <a href='/innovshop/cgv' target='_blank'>conditions générales de vente</a>",
                'label_html' => true,
                'constraints' => [new Assert\IsTrue(message: 'Vous devez accepter les CGV')],
            ])
        ;
    }
}
