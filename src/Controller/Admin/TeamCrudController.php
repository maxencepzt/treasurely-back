<?php

namespace App\Controller\Admin;

use App\Entity\Team;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Team>
 */
class TeamCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Team::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Équipe')
            ->setEntityLabelInPlural('Équipes')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l\'équipe');
    }

    /**
     * `Team` est abstraite et absente de la carte de discrimination : une équipe naît
     * joueuse ou conceptrice, jamais nue. L'admin liste et modifie, il ne crée pas.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW);
    }

    /**
     * Une suppression refusée par la base (clé étrangère) revient à la liste avec un message,
     * au lieu de la page d'erreur 409 qu'EasyAdmin lève par défaut.
     *
     * @param object $entityInstance
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            parent::deleteEntity($entityManager, $entityInstance);
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('danger', sprintf('« %s » ne peut pas être supprimé : d\'autres éléments en dépendent encore.', (string) $entityInstance));
        }
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name', 'Nom'),
            TextareaField::new('description', 'Description'),
            AssociationField::new('owner', 'Propriétaire'),
            AssociationField::new('members', 'Membres'),
            AssociationField::new('image', 'Image'),
            ChoiceField::new('type', 'Type')
                ->setChoices(['Joueurs' => 'player', 'Concepteurs' => 'designer'])
                ->renderAsBadges(['player' => 'info', 'designer' => 'success'])
                ->hideOnForm(),
        ];
    }
}
