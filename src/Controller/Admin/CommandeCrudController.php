<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class CommandeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Commande::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined()
            ->setSearchFields([
                'reference',
                'status',
                'total',
                'user.email',
                'user.Nom',
                'user.prenom',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $a) => $a->setLabel('Modifier'))
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn(Action $a) => $a->setLabel('Modifier'));
    }

    public function configureFields(string $pageName): iterable
    {
        $id      = IdField::new('id', 'ID');
        $ref     = TextField::new('reference', 'Référence');
        $created = DateTimeField::new('createdAt', 'Créée le');
        $total   = TextField::new('total', 'Total (€)');

        /** @var ChoiceField $status */
        $status = ChoiceField::new('status', 'Statut');
        $status
            ->setChoices([
                'Payée'           => 'paid',
                'En préparation'  => 'processing',
                'Expédiée'        => 'shipped',
                'Livrée'          => 'delivered',
                'Annulée'         => 'cancelled',
                'Remboursée'      => 'refunded',
            ])
            ->renderAsBadges([
                'paid'       => 'success',
                'processing' => 'warning',
                'shipped'    => 'info',
                'delivered'  => 'primary',
                'cancelled'  => 'danger',
                'refunded'   => 'secondary',
            ]);

        $user = AssociationField::new('user', 'Client');

        // --- Champs virtuels pour le détail ---
        /** @var Field $shipping */
        $shipping = Field::new('shippingBlock', 'Adresse de livraison');
        $shipping
            ->setTemplatePath('admin/fields/shipping_address.html.twig')
            ->setVirtual(true)
            ->onlyOnDetail();

        /** @var Field $items */
        $items = Field::new('itemsBlock', 'Articles commandés');
        $items
            ->setTemplatePath('admin/fields/order_items.html.twig')
            ->setVirtual(true)
            ->onlyOnDetail();

        if ($pageName === Crud::PAGE_INDEX) {
            return [$id, $ref, $created, $user, $total, $status];
        }

        if ($pageName === Crud::PAGE_EDIT || $pageName === Crud::PAGE_NEW) {
            return [$ref, $created, $total, $status];
        }

        // PAGE_DETAIL
        return [$id, $ref, $created, $user, $total, $status, $shipping, $items];
    }

    public function configureFilters(Filters $filters): Filters
    {
        /** @var ChoiceFilter $statusFilter */
        $statusFilter = ChoiceFilter::new('status', 'Statut');
        $statusFilter->setChoices([
            'Payée'          => 'paid',
            'En préparation' => 'processing',
            'Expédiée'       => 'shipped',
            'Livrée'         => 'delivered',
            'Annulée'        => 'cancelled',
            'Remboursée'     => 'refunded',
        ]);

        return $filters
            ->add(DateTimeFilter::new('createdAt', 'Date'))
            ->add($statusFilter)
            ->add(EntityFilter::new('user', 'Client'));
    }
}
