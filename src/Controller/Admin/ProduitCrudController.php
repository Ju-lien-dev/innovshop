<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class ProduitCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Produit::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $descriptionForm = TextEditorField::new('description', 'Description')
            ->onlyOnForms();

        $descriptionDetail = TextField::new('description', 'Description')
            ->renderAsHtml()
            ->onlyOnDetail();

        $descriptionIndex = TextField::new('description', 'Description')
            ->onlyOnIndex()
            ->formatValue(
                fn($value) =>
                mb_strimwidth(strip_tags($value ?? ''), 0, 100, '…')
            );

        return [
            TextField::new('titre')->setColumns(7),
            $descriptionForm,
            $descriptionDetail,
            $descriptionIndex,
            MoneyField::new('prix')->setColumns(7)->setNumDecimals(2)->setHelp('Prix en euros')->setCurrency('EUR'),
            IntegerField::new('quantiteRestante')->setColumns(7),
            AssociationField::new('categorie')
                ->setColumns(7)
                ->setRequired(false)
                ->setHelp('Sélectionnez une ou plusieurs catégories pour ce produit'),
            ImageField::new('image')
                ->setRequired($pageName === Crud::PAGE_NEW)
                ->setBasePath('images')
                ->setUploadDir('public/images')
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setColumns(7)
                ->setHelp('Téléchargez une image pour ce produit'),
            ImageField::new('images')
                ->setBasePath('images')
                ->setUploadDir('public/images')
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setColumns(7)
                ->setHelp('Images supplémentaires du produit')
                ->setRequired(false)
                ->setFormTypeOption('allow_add', true)
                ->setFormTypeOption('allow_delete', true)
                ->setFormTypeOption('by_reference', false)
                ->setFormTypeOption('multiple', true)
        ];
    }
}
