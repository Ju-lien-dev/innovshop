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
        /** @var TextField $titre */
        $titre = TextField::new('titre', 'Titre');
        $titre->setColumns(9)->setRequired(true);

        /** @var TextEditorField $texte */
        $texte = TextEditorField::new('texte', 'Texte');
        $texte->setColumns(9)->setRequired(true);

        /** @var ImageField $image */
        $image = ImageField::new('image', 'Image');
        $image
            ->setColumns(9)
            ->setRequired(false)
            ->setBasePath('images')
            ->setUploadDir('public/images')
            ->setHelp('Téléchargez une image pour ce blog');

        yield $titre;
        yield $texte;
        yield $image;
    }
}
