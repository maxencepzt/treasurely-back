<?php

namespace App\Controller\Admin;

use App\Entity\QRRiddle;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<QRRiddle>
 */
class QRRiddleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return QRRiddle::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Énigme QR')
            ->setEntityLabelInPlural('Énigmes QR')
            ->setPageTitle(Crud::PAGE_NEW, 'Nouvelle énigme QR')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l\'énigme QR');
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
            TextField::new('code', 'Code QR'),
            AssociationField::new('hunt', 'Chasse au trésor'),
            AssociationField::new('participateRiddles', 'Participations')->hideOnForm(),
        ];
    }
}
