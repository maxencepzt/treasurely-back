<?php

namespace App\Controller\Admin;

use App\Entity\HuntType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
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

    /**
     * Une suppression refusée par la base (clé étrangère) revient à la liste avec un message,
     * au lieu de la page d'erreur 409 qu'EasyAdmin lève par défaut.
     *
     * @param object $entityInstance
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            parent::deleteEntity($entityManager, $entityInstance);
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('danger', sprintf('« %s » ne peut pas être supprimé : d\'autres éléments en dépendent encore.', (string) $entityInstance));
        }
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
