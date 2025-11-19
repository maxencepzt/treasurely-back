<?php

namespace App\Controller\Admin;

use App\Entity\GPSRiddle;
use App\Entity\HuntType;
use App\Entity\MCQRiddle;
use App\Entity\ParticipateHunt;
use App\Entity\ParticipateRiddle;
use App\Entity\Picture;
use App\Entity\QRRiddle;
use App\Entity\Riddle;
use App\Entity\Team;
use App\Entity\TextRiddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Treasurely');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        // Vérifier si l'utilisateur est une instance de notre entité User
        if (!$user instanceof User) {
            return parent::configureUserMenu($user);
        }

        $userMenu = parent::configureUserMenu($user);

        // Si l'utilisateur a une photo de profil, l'utiliser comme avatar
        if (null !== $user->getProfilePicture()) {
            $pictureUrl = '/api/users/'.$user->getId().'/picture';
            $userMenu->setAvatarUrl($pictureUrl);
        }

        return $userMenu
            ->setName($user->getNickname())
            ->displayUserName();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        // Gestion des chasses au trésor
        yield MenuItem::section('Chasses au trésor');
        yield MenuItem::linkToCrud('Chasses au trésor', 'fa fa-coins', TreasureHunt::class);
        yield MenuItem::linkToCrud('Types de chasse', 'fa fa-tag', HuntType::class);
        yield MenuItem::linkToCrud('Participations chasses', 'fa fa-users', ParticipateHunt::class);

        // Gestion des énigmes
        yield MenuItem::section('Énigmes');
        yield MenuItem::linkToCrud('Énigmes', 'fa fa-puzzle-piece', Riddle::class);
        yield MenuItem::linkToCrud('Énigmes GPS', 'fa fa-map-marker-alt', GPSRiddle::class);
        yield MenuItem::linkToCrud('Énigmes QCM', 'fa fa-question-circle', MCQRiddle::class);
        yield MenuItem::linkToCrud('Énigmes QR', 'fa fa-qrcode', QRRiddle::class);
        yield MenuItem::linkToCrud('Énigmes Texte', 'fa fa-align-left', TextRiddle::class);
        yield MenuItem::linkToCrud('Participations énigmes', 'fa fa-check-square', ParticipateRiddle::class);

        // Gestion générale
        yield MenuItem::section('Général');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-user', User::class);
        yield MenuItem::linkToCrud('Équipes', 'fa fa-users', Team::class);
        yield MenuItem::linkToCrud('Images', 'fa fa-image', Picture::class);
    }
}
