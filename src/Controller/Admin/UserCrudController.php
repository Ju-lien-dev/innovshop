<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;


class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setPageTitle('index', 'Utilisateurs')
            ->setDefaultSort(['email' => 'ASC'])
            ->setPaginatorPageSize(20)
            ->setSearchFields(['email']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('email', 'Email'))
            ->add(
                ChoiceFilter::new('roles', 'Rôles')
                    ->setChoices([
                        'Administrateur' => 'ROLE_ADMIN',
                        'Utilisateur'    => 'ROLE_USER',
                    ])
                    ->canSelectMultiple()
            );
    }

    public function configureFields(string $pageName): iterable
    {
        $email = EmailField::new('email', 'Email');

        // Affichage lisible via User::getRolesAsText()
        $rolesText = TextField::new('rolesAsText', 'Rôles');

        if ($pageName === Crud::PAGE_INDEX) {
            return [$email, $rolesText];
        }

        if ($pageName === Crud::PAGE_DETAIL) {
            return [$email, $rolesText];
        }

        // ------- Formulaires (NEW/EDIT) -------
        /** @var ChoiceField $rolesForm */
        $rolesForm = ChoiceField::new('roles', 'Rôles');
        // Déclaré explicitement comme ChoiceField pour l’analyseur
        $rolesForm->setChoices([
            'Administrateur' => 'ROLE_ADMIN',
            'Utilisateur'    => 'ROLE_USER',
        ]);
        $rolesForm->allowMultipleChoices();
        $rolesForm->renderExpanded(false); // <select multiple>
        $rolesForm->setRequired(true);

        return [$email, $rolesForm];
    }
}
