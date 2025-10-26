<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories')
            ->setPageTitle('index', 'Catégories')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'description'])
            ->setPaginatorPageSize(20);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'Nom'))
            ->add(TextFilter::new('description', 'Description'));
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var TextField $name */
        $name = TextField::new('name', 'Nom de la catégorie');
        $name->setColumns(9)->setHelp('Entrez un nom court et descriptif.');

        /** @var TextEditorField $description */
        $description = TextEditorField::new('description', 'Description');
        $description->setColumns(9)->setHelp('Facultatif : ajoutez quelques détails sur la catégorie.');

        yield $name;
        yield $description;
    }
}
