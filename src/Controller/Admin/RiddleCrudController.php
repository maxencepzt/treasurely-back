<?php

namespace App\Controller\Admin;

use App\Entity\Riddle;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Riddle>
 */
class RiddleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Riddle::class;
    }

    /**
     * `Riddle` est abstraite : une énigme se crée depuis le CRUD de son type (GPS, QCM, QR, texte).
     * Ce CRUD liste et modifie toutes les énigmes, il n'en crée pas.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW);
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
            AssociationField::new('hunt', 'Chasse au trésor'),
            AssociationField::new('participateRiddles', 'Participations')->hideOnForm(),
        ];
    }
}
