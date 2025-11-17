<?php

namespace App\Controller\Admin;

use App\Entity\ParticipateRiddle;
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

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('hunter', 'Chasseur'),
            AssociationField::new('riddle', 'Énigme'),
            DateTimeField::new('startTime', 'Heure de début'),
            DateTimeField::new('finishTime', 'Heure de fin'),
            IntegerField::new('score', 'Score'),
            DateTimeField::new('lastParticipate', 'Dernière participation'),
        ];
    }
}
