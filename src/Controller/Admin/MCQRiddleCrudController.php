<?php

namespace App\Controller\Admin;

use App\Entity\MCQRiddle;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<MCQRiddle>
 */
class MCQRiddleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MCQRiddle::class;
    }

    /**
     * Les énigmes se créent et se suppriment depuis la façade designer, dont HuntEditor
     * tient à jour `riddleCount` et l'ordre des énigmes de la chasse. Le CRUD corrige une
     * énigme existante, il n'en crée ni n'en supprime.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Énigme QCM')
            ->setEntityLabelInPlural('Énigmes QCM')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l\'énigme QCM');
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
            ArrayField::new('choices', 'Choix proposés'),
            ArrayField::new('answers', 'Bonnes réponses'),
            AssociationField::new('hunt', 'Chasse au trésor'),
            AssociationField::new('participateRiddles', 'Participations')->hideOnForm(),
        ];
    }
}
