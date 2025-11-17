<?php

namespace App\Controller\Admin;

use App\Entity\ParticipateHunt;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
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
            DateField::new('lastParticipate', 'Dernière participation'),
        ];
    }
}
