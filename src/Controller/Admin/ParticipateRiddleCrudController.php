<?php

namespace App\Controller\Admin;

use App\Entity\ParticipateRiddle;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

/**
 * @extends AbstractCrudController<ParticipateRiddle>
 */
class ParticipateRiddleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ParticipateRiddle::class;
    }

    /**
     * Une participation à une énigme naît quand le joueur lit son énigme courante
     * (`RiddleProvider`), avec l'horloge du serveur : elle ne se crée pas à la main.
     * L'admin consulte et corrige, il ne crée pas.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Participation à une énigme')
            ->setEntityLabelInPlural('Participations aux énigmes')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la participation');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('hunter', 'Chasseur'),
            AssociationField::new('riddle', 'Énigme'),
            // Horloges du jeu, posées par le serveur à la seconde : affichées, jamais saisies (le formulaire perdait les secondes, et startTime fait le score).
            DateTimeField::new('startTime', 'Heure de début')->hideOnForm(),
            DateTimeField::new('finishTime', 'Heure de fin')->hideOnForm(),
            IntegerField::new('score', 'Score'),
            DateTimeField::new('lastParticipate', 'Dernière participation')->hideOnForm(),
        ];
    }
}
