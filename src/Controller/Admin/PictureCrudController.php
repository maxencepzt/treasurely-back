<?php

namespace App\Controller\Admin;

use App\Entity\Picture;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;

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
            ->setEntityLabelInSingular('Image')
            ->setEntityLabelInPlural('Images')
            ->setPageTitle(Crud::PAGE_NEW, 'Nouvelle image')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l\'image')
            ->setPageTitle('detail', 'Image #%entity_id%')
            ->setHelp('index', 'Les images sont stockées en base de données (BLOB). Uploadez une image pour la stocker.')
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
            ->add(Crud::PAGE_INDEX, $viewImageAction)
            ->add(Crud::PAGE_DETAIL, $viewImageAction);
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
        $fields = [
            IdField::new('id', 'ID')->hideOnForm(),
        ];

        if (Crud::PAGE_NEW === $pageName || Crud::PAGE_EDIT === $pageName) {
            $fields[] = Field::new('uploadedFile', 'Image')
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                    'mapped' => false,
                    'required' => Crud::PAGE_NEW === $pageName,
                    'constraints' => [
                        new File([
                            'maxSize' => '5M',
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/jpg',
                                'image/png',
                                'image/gif',
                            ],
                            'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, GIF)',
                        ]),
                    ],
                ])
                ->setHelp('Formats acceptés : PNG, JPG, JPEG, GIF (max 5MB). L\'image sera stockée directement en base de données.');
        }

        return $fields;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleImageUpload($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleImageUpload($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function handleImageUpload(Picture $picture): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ($request) {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $request->files->get('Picture')['uploadedFile'] ?? null;

            if ($uploadedFile instanceof UploadedFile) {
                // Lire directement le contenu du fichier en mémoire sans le stocker
                $imageContent = file_get_contents($uploadedFile->getPathname());

                if (false !== $imageContent) {
                    $picture->setImage($imageContent);
                }
            }
        }
    }
}
