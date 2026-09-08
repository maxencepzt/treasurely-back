<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\Gender;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @extends AbstractCrudController<User>
 */
class UserCrudController extends AbstractCrudController
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setPageTitle(Crud::PAGE_NEW, 'Nouvel utilisateur')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l\'utilisateur')
            ->setDefaultSort(['id' => 'DESC']);
    }

    /**
     * @return FormBuilderInterface<User>
     */
    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return $this->hashPasswordOnSubmit(parent::createNewFormBuilder($entityDto, $formOptions, $context));
    }

    /**
     * @return FormBuilderInterface<User>
     */
    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return $this->hashPasswordOnSubmit(parent::createEditFormBuilder($entityDto, $formOptions, $context));
    }

    /**
     * Le mot de passe saisi n'est pas mappé sur l'entité : il est haché ici, et seulement
     * s'il a été renseigné, pour qu'une modification sans saisie garde le mot de passe actuel.
     *
     * @param FormBuilderInterface<User> $formBuilder
     *
     * @return FormBuilderInterface<User>
     */
    private function hashPasswordOnSubmit(FormBuilderInterface $formBuilder): FormBuilderInterface
    {
        return $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $plainPassword = $form->get('plainPassword')->getData();
            if (!$form->isValid() || null === $plainPassword || '' === $plainPassword) {
                return;
            }

            $user = $form->getData();
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        });
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
            TextField::new('nickname', 'Pseudo'),
            TextField::new('plainPassword', 'Mot de passe')
                ->setFormType(PasswordType::class)
                ->setFormTypeOptions(['mapped' => false, 'required' => Crud::PAGE_NEW === $pageName])
                ->setHelp(Crud::PAGE_NEW === $pageName ? '' : 'Laisser vide pour conserver le mot de passe actuel.')
                ->onlyOnForms(),
            TextField::new('firstname', 'Prénom'),
            TextField::new('lastname', 'Nom'),
            EmailField::new('email', 'Email'),
            TelephoneField::new('phone', 'Téléphone'),
            DateField::new('birthDate', 'Date de naissance'),
            ChoiceField::new('gender', 'Genre')
                ->setChoices([
                    'Homme' => Gender::MAN,
                    'Femme' => Gender::WOMAN,
                    'Autre' => Gender::OTHER,
                ])
                ->formatValue(function ($value) {
                    return match ($value) {
                        Gender::MAN => 'Homme',
                        Gender::WOMAN => 'Femme',
                        Gender::OTHER => 'Autre',
                        default => 'Non spécifié',
                    };
                }),
            BooleanField::new('activated', 'Activé'),
            BooleanField::new('public', 'Profil public'),
            ChoiceField::new('roles', 'Rôles')
                ->setChoices([
                    'Admin' => 'ROLE_ADMIN',
                    'Utilisateur' => 'ROLE_USER',
                ])
                ->setFormTypeOptions([
                    'multiple' => true,
                ])
                ->setSortable(false)
                ->formatValue(function ($roles) {
                    if (!is_array($roles)) {
                        return (string) $roles;
                    }

                    $badges = [];
                    if (in_array('ROLE_ADMIN', $roles, true)) {
                        $badges[] = '<span class="badge badge-danger"><i class="fa fa-shield-alt"></i> Admin</span>';
                    }
                    if (in_array('ROLE_USER', $roles, true)) {
                        $badges[] = '<span class="badge badge-primary"><i class="fa fa-user"></i> Utilisateur</span>';
                    }

                    if (empty($badges)) {
                        return '<span class="badge badge-secondary">Aucun</span>';
                    }

                    return implode(' ', $badges);
                }),
            DateField::new('creationDate', 'Date de création')->hideOnForm(),
            AssociationField::new('ownedTeams', 'Équipes créées')->hideOnForm(),
            AssociationField::new('teams', 'Membre des équipes'),
            AssociationField::new('participateRiddles', 'Énigmes participées')->hideOnForm(),
        ];
    }
}
