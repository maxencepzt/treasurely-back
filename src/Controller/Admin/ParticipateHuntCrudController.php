<?php

namespace App\Controller\Admin;

use App\Entity\ParticipateHunt;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

/**
 * @extends AbstractCrudController<ParticipateHunt>
 */
class ParticipateHuntCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ParticipateHunt::class;
    }

    /**
     * Une participation naît quand un joueur rejoint une chasse (`JoinHuntProcessor`), qui
     * pose l'énigme courante et l'horloge : elle ne se crée pas à la main. L'admin consulte,
     * corrige et supprime, il ne crée pas.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Participation à une chasse')
            ->setEntityLabelInPlural('Participations aux chasses')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la participation');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('hunter', 'Chasseur'),
            AssociationField::new('hunt', 'Chasse au trésor'),
            ChoiceField::new('rate', 'Note')
                ->setChoices([
                    '0 étoile' => 0,
                    '1 étoile' => 1,
                    '2 étoiles' => 2,
                    '3 étoiles' => 3,
                    '4 étoiles' => 4,
                    '5 étoiles' => 5,
                ])
                ->allowMultipleChoices(false),
            IntegerField::new('time', 'Temps (secondes)'),
            IntegerField::new('score', 'Score'),
            BooleanField::new('finished', 'Terminé'),
            // Horloge du jeu, posée par le serveur : affichée, jamais saisie (un DateField la tronquait à minuit).
            DateTimeField::new('lastParticipate', 'Dernière participation')->hideOnForm(),
        ];
    }
}
