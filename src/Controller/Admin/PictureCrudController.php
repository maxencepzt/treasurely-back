<?php

namespace App\Controller\Admin;

use App\Entity\Picture;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

/**
 * @extends AbstractCrudController<Picture>
 */
class PictureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Picture::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Images')
            ->setPageTitle('detail', 'Image #%entity_id%')
            ->setHelp('index', 'Les images sont stockées en base de données (BLOB). Cliquez sur "Afficher" pour voir le lien API.')
            ->setHelp('detail', 'Utilisez le lien ci-dessous pour visualiser l\'image.')
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewImageAction = Action::new('viewImage', 'Voir l\'image', 'fa fa-image')
            ->linkToUrl(function (Picture $entity): string {
                return '/api/pictures/'.$entity->getId();
            })
            ->setHtmlAttributes(['target' => '_blank'])
            ->setCssClass('btn btn-primary');

        return $actions
            ->add(Crud::PAGE_INDEX, $viewImageAction);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->hideOnForm(),
        ];
    }
}
