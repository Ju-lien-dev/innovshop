<?php

namespace App\Controller\Admin;

use App\Entity\ShippingMethod;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Integer;

class ShippingMethodCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ShippingMethod::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setColumns(9)->setLabel('Titre du transport'),
            TextEditorField::new('description')->setColumns(9),
            FormField::addRow(),
            MoneyField::new('amount_cents', 'Montant')
                ->setCurrency('EUR')
                ->setStoredAsCents(true)
                ->setNumDecimals(2)
                ->setColumns(6),
            FormField::addRow(),
            IntegerField::new('min_days', 'Délai min (jours)')->setColumns(3),
            IntegerField::new('max_days', 'Délai max (jours)')->setColumns(3),
            FormField::addRow(),
            BooleanField::new('is_active', 'Activer/Désactiver')->setColumns(12),
        ];
    }
}
