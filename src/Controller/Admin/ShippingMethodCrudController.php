<?php

namespace App\Controller\Admin;

use App\Entity\ShippingMethod;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

class ShippingMethodCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ShippingMethod::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Méthode de livraison')
            ->setEntityLabelInPlural('Livraisons')
            ->setPageTitle('index', 'Livraisons')
            ->setDefaultSort(['name' => 'ASC'])
            ->setPaginatorPageSize(20)
            ->setSearchFields(['name', 'description']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isActive', 'Actif'))
            ->add(NumericFilter::new('amountCents', 'Montant (cts)'))
            ->add(NumericFilter::new('minDays', 'Délai min'))
            ->add(NumericFilter::new('maxDays', 'Délai max'));
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var TextField $name */
        $name = TextField::new('name', 'Titre du transport');
        $name = $name->setColumns(9);

        /** @var TextEditorField $desc */
        $desc = TextEditorField::new('description', 'Description');
        $desc = $desc->setColumns(9);

        /** @var MoneyField $amount */
        $amount = MoneyField::new('amountCents', 'Tarif');
        $amount = $amount->setCurrency('EUR');
        $amount = $amount->setStoredAsCents(true);
        $amount = $amount->setNumDecimals(2);
        $amount = $amount->setColumns(6);

        /** @var IntegerField $min */
        $min = IntegerField::new('minDays', 'Délai min (jours)');
        $min = $min->setColumns(3);

        /** @var IntegerField $max */
        $max = IntegerField::new('maxDays', 'Délai max (jours)');
        $max = $max->setColumns(3);

        /** @var BooleanField $active */
        $active = BooleanField::new('isActive', 'Actif');
        $active = $active->setColumns(12);

        // Colonne virtuelle "Délai" pour la liste
        /** @var TextField $delayInline */
        $delayInline = TextField::new('delayAsText', 'Délai');
        $delayInline = $delayInline->onlyOnIndex();
        $delayInline = $delayInline->setSortable(false);

        if (Crud::PAGE_INDEX === $pageName) {
            return [$name, $amount, $delayInline, $active];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $name,
                $desc,
                FormField::addRow(),
                $amount,
                FormField::addRow(),
                $min,
                $max,
                FormField::addRow(),
                $active,
            ];
        }

        // new & edit
        return [
            $name,
            $desc,
            FormField::addRow(),
            $amount,
            FormField::addRow(),
            $min,
            $max,
            FormField::addRow(),
            $active,
        ];
    }
}
