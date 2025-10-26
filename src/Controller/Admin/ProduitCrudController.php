<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class ProduitCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Produit::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setPageTitle(Crud::PAGE_INDEX, 'Produits')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un produit')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le produit')
            ->setDefaultSort(['titre' => 'ASC'])
            ->setSearchFields(['titre', 'description']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('titre', 'Titre'))
            ->add(EntityFilter::new('categorie', 'Catégorie'));
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_INDEX) {
            /** @var ImageField $imageIndex */
            $imageIndex = ImageField::new('image', 'Image');
            $imageIndex->setBasePath('images')->onlyOnIndex();

            /** @var TextField $titleIndex */
            $titleIndex = TextField::new('titre', 'Titre');

            /** @var TextField $descIndex */
            $descIndex = TextField::new('description', 'Description');
            $descIndex
                ->onlyOnIndex()
                ->formatValue(fn($v) => mb_strimwidth(strip_tags((string) $v), 0, 90, '…'));

            /** @var MoneyField $priceIndex */
            $priceIndex = MoneyField::new('prix', 'Prix');
            $priceIndex->setCurrency('EUR')->setNumDecimals(2);

            /** @var IntegerField $stockIndex */
            $stockIndex = IntegerField::new('quantiteRestante', 'Stock');

            /** @var AssociationField $catIndex */
            $catIndex = AssociationField::new('categorie', 'Catégorie');
            $catIndex->formatValue(function ($value) {
                if (\is_iterable($value)) {
                    $names = [];
                    foreach ($value as $cat) {
                        $names[] = (string) $cat;
                    }
                    return implode(', ', $names);
                }
                return (string) $value;
            });

            yield $imageIndex;
            yield $titleIndex;
            yield $descIndex;
            yield $priceIndex;
            yield $stockIndex;
            yield $catIndex;
            return;
        }

        if ($pageName === Crud::PAGE_DETAIL) {
            /** @var ImageField $imageDetail */
            $imageDetail = ImageField::new('image', 'Image');
            $imageDetail->setBasePath('images');

            /** @var TextField $titleDetail */
            $titleDetail = TextField::new('titre', 'Titre');

            /** @var TextField $descDetail */
            $descDetail = TextField::new('description', 'Description');
            $descDetail->renderAsHtml();

            /** @var MoneyField $priceDetail */
            $priceDetail = MoneyField::new('prix', 'Prix');
            $priceDetail->setCurrency('EUR')->setNumDecimals(2);

            /** @var IntegerField $stockDetail */
            $stockDetail = IntegerField::new('quantiteRestante', 'Stock');

            /** @var AssociationField $catDetail */
            $catDetail = AssociationField::new('categorie', 'Catégorie');

            yield $imageDetail;
            yield $titleDetail;
            yield $descDetail;
            yield $priceDetail;
            yield $stockDetail;
            yield $catDetail;
            return;
        }

        // NEW / EDIT
        yield FormField::addPanel('Informations produit')->setColumns(12);

        /** @var TextField $titleForm */
        $titleForm = TextField::new('titre', 'Titre');
        $titleForm->setColumns(12);

        /** @var TextEditorField $descForm */
        $descForm = TextEditorField::new('description', 'Description');
        $descForm->setColumns(12);

        /** @var MoneyField $priceForm */
        $priceForm = MoneyField::new('prix', 'Prix');
        $priceForm->setCurrency('EUR')->setNumDecimals(2)->setHelp('Prix en euros')->setColumns(12);

        /** @var IntegerField $stockForm */
        $stockForm = IntegerField::new('quantiteRestante', 'Stock');
        $stockForm->setColumns(12);

        /** @var AssociationField $catForm */
        $catForm = AssociationField::new('categorie', 'Catégorie');
        $catForm->setRequired(false)->setHelp('Sélectionnez une catégorie')->setColumns(12);

        yield $titleForm;
        yield $descForm;
        yield $priceForm;
        yield $stockForm;
        yield $catForm;

        yield FormField::addPanel('Médias')->setColumns(12);

        /** @var ImageField $imageForm */
        $imageForm = ImageField::new('image', 'Image principale');
        $imageForm
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->setBasePath('images')
            ->setUploadDir('public/images')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setHelp('Téléchargez une image pour ce produit')
            ->setColumns(12);

        yield $imageForm;
    }
}
