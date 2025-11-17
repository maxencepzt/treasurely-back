<?php

namespace App\Controller\Admin;

use App\Entity\GPSRiddle;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<GPSRiddle>
 */
class GPSRiddleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return GPSRiddle::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            TextareaField::new('description', 'Description'),
            ChoiceField::new('difficulty', 'Difficulté')
                ->setChoices([
                    'Facile' => 1,
                    'Moyen' => 2,
                    'Difficile' => 3,
                ]),
            IntegerField::new('orderNumber', 'Ordre'),
            NumberField::new('latitude', 'Latitude')->setNumDecimals(8),
            NumberField::new('longitude', 'Longitude')->setNumDecimals(8),
            AssociationField::new('hunt', 'Chasse au trésor'),
            AssociationField::new('participateRiddles', 'Participations')->hideOnForm(),
        ];
    }
}
