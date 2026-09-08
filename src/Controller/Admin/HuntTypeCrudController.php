<?php

namespace App\Controller\Admin;

use App\Entity\HuntType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<HuntType>
 */
class HuntTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return HuntType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Type de chasse')
            ->setEntityLabelInPlural('Types de chasse')
            ->setPageTitle(Crud::PAGE_NEW, 'Nouveau type de chasse')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le type de chasse');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            AssociationField::new('treasureHunts', 'Chasses au trésor')->hideOnForm(),
        ];
    }
}
