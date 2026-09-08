<?php

namespace App\Controller\Admin;

use App\Entity\TeamJoinRequest;
use App\Enum\JoinRequestStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

/**
 * @extends AbstractCrudController<TeamJoinRequest>
 */
class TeamJoinRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TeamJoinRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande d\'adhésion')
            ->setEntityLabelInPlural('Demandes d\'adhésion')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la demande')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    /**
     * Une demande naît dans l'application, quand un joueur la fait depuis l'annuaire
     * (RequestToJoinProcessor). L'admin consulte, corrige un statut, supprime.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('team', 'Équipe')->hideOnForm(),
            AssociationField::new('user', 'Joueur')->hideOnForm(),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En attente' => JoinRequestStatus::PENDING,
                    'Acceptée' => JoinRequestStatus::ACCEPTED,
                    'Refusée' => JoinRequestStatus::REFUSED,
                ])
                ->renderAsBadges([
                    JoinRequestStatus::PENDING->value => 'warning',
                    JoinRequestStatus::ACCEPTED->value => 'success',
                    JoinRequestStatus::REFUSED->value => 'danger',
                ]),
            DateTimeField::new('createdAt', 'Envoyée le')->hideOnForm(),
            DateTimeField::new('decidedAt', 'Traitée le')->hideOnForm(),
        ];
    }
}
