<?php

namespace App\Controller\Admin;

use App\Entity\TreasureHunt;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
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

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            TextareaField::new('description', 'Description'),
            BooleanField::new('public', 'Public'),
            ChoiceField::new('difficulty', 'Difficulté')
                ->setChoices([
                    'Facile' => 1,
                    'Moyen' => 2,
                    'Difficile' => 3,
                ]),
            IntegerField::new('riddleCount', 'Nombre d\'énigmes'),
            AssociationField::new('owner', 'Propriétaire'),
            AssociationField::new('team', 'Équipe'),
            AssociationField::new('huntType', 'Types de chasse'),
            AssociationField::new('image', 'Image'),
            AssociationField::new('riddles', 'Énigmes')->hideOnForm(),
        ];
    }
}
