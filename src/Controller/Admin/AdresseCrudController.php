<?php

namespace App\Controller\Admin;

use App\Entity\Adresse;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdresseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Adresse::class;
    }
    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Travail'  => 'work',
                'Domicile' => 'home',
                'Autre'    => 'other',
            ])
            ->allowMultipleChoices(false)
            ->renderExpanded(false) // select
            ->setRequired(true);

        yield TextField::new('nom', 'Nom')
            ->setRequired(true);

        yield TextField::new('adresse', 'Adresse')
            ->setRequired(true);

        yield TextField::new('ville', 'Ville')
            ->setRequired(true);

        // TextField pour garder les zéros éventuels et contrôler la longueur (FR = 5)
        yield TextField::new('codePostal', 'Code Postal')
            ->setMaxLength(5)
            ->setRequired(true);
    }
}
