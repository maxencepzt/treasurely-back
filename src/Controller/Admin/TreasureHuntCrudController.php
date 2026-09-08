<?php

namespace App\Controller\Admin;

use App\Entity\TreasureHunt;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<TreasureHunt>
 */
class TreasureHuntCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TreasureHunt::class;
    }

    /**
     * Une chasse se construit dans la façade designer, dont HuntEditor pose le propriétaire,
     * le nombre d'énigmes et le statut du workflow. L'admin consulte, corrige et supprime,
     * il ne crée pas.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Chasse au trésor')
            ->setEntityLabelInPlural('Chasses au trésor')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la chasse au trésor');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            TextareaField::new('description', 'Description')->hideOnIndex(),
            // Le statut est piloté par le workflow (publish / close / republish), pas par l'admin.
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'Brouillon' => TreasureHunt::STATE_DRAFT,
                    'Ouverte' => TreasureHunt::STATE_OPENED,
                    'Fermée' => TreasureHunt::STATE_CLOSED,
                ])
                ->hideOnForm(),
            TextField::new('location', 'Lieu'),
            ChoiceField::new('difficulty', 'Difficulté')
                ->setChoices([
                    'Facile' => 1,
                    'Moyen' => 2,
                    'Difficile' => 3,
                ]),
            IntegerField::new('estimatedTime', 'Durée estimée (minutes)'),
            // Recalculé par HuntEditor à chaque enregistrement des énigmes.
            IntegerField::new('riddleCount', 'Nombre d\'énigmes')->hideOnForm(),
            AssociationField::new('owner', 'Propriétaire'),
            AssociationField::new('designerTeam', 'Équipe conceptrice'),
            AssociationField::new('huntType', 'Types de chasse'),
            AssociationField::new('image', 'Image'),
            AssociationField::new('riddles', 'Énigmes')->hideOnForm(),
            DateTimeField::new('createdAt', 'Créée le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Modifiée le')->hideOnForm(),
        ];
    }
}
