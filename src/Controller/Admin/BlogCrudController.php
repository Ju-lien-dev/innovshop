<?php

namespace App\Controller\Admin;

use App\Entity\Blog;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;





class BlogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Blog::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('titre')
                ->setColumns(9)
                ->setRequired(true),
            TextEditorField::new('texte')
                ->setColumns(9)
                ->setRequired(true),
            ImageField::new('image')->setColumns(9)
                ->setRequired(false)
                ->setBasePath('images')
                ->setUploadDir('public/images')
                ->setHelp('Téléchargez une image pour ce blog'),

        ];
    }
}
