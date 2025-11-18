<?php

namespace App\Controller\Admin;

use App\Entity\HuntType;
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

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            AssociationField::new('treasureHunts', 'Chasses au trésor')->hideOnForm(),
        ];
    }
}
