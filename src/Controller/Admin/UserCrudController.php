<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\Gender;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<User>
 */
class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('nickname', 'Pseudo'),
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
            ArrayField::new('roles', 'Rôles'),
            DateField::new('creationDate', 'Date de création')->hideOnForm(),
            AssociationField::new('ownedTeams', 'Équipes créées')->hideOnForm(),
            AssociationField::new('teams', 'Membre des équipes'),
            AssociationField::new('participateRiddles', 'Énigmes participées')->hideOnForm(),
        ];
    }
}
