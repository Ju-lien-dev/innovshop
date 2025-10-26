<?php

namespace App\Controller\Admin;

use App\Entity\Adresse;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdresseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Adresse::class;
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var ChoiceField $type */
        $type = ChoiceField::new('type', 'Type');
        $type
            ->setChoices([
                'Travail'  => 'work',
                'Domicile' => 'home',
                'Autre'    => 'other',
            ])
            ->allowMultipleChoices(false)
            ->renderExpanded(false)
            ->setRequired(true);

        /** @var TextField $nom */
        $nom = TextField::new('nom', 'Nom');
        $nom->setRequired(true);

        /** @var TextField $adresse */
        $adresse = TextField::new('adresse', 'Adresse');
        $adresse->setRequired(true);

        /** @var TextField $ville */
        $ville = TextField::new('ville', 'Ville');
        $ville->setRequired(true);

        /** @var TextField $codePostal */
        $codePostal = TextField::new('codePostal', 'Code Postal');
        $codePostal->setMaxLength(5)->setRequired(true);

        yield $type;
        yield $nom;
        yield $adresse;
        yield $ville;
        yield $codePostal;
    }
}
