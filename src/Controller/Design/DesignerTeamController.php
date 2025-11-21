<?php

namespace App\Controller\Design;

use App\Entity\DesignerTeam;
use App\Entity\User;
use App\Repository\DesignerTeamRepository;
use App\Repository\UserRepository;
use App\Service\ImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DesignerTeamController extends AbstractController
{
    public function __construct(private readonly Security $security)
    {
    }

    #[Route('/designer/team', name: 'app_designer_team')]
    public function index(DesignerTeamRepository $designerTeamRepository): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();

        // Récupérer les équipes dont l'utilisateur est propriétaire
        $ownedTeams = $designerTeamRepository->findByOwner($currentUser);

        // Récupérer les équipes dont l'utilisateur est membre (mais pas propriétaire)
        $memberTeams = $designerTeamRepository->findByMemberOnly($currentUser);

        return $this->render('designer/team/index.html.twig', [
            'ownedTeams' => $ownedTeams,
            'memberTeams' => $memberTeams,
        ]);
    }

    #[Route('/designer/team/details/{id}', name: 'app_designer_team_details')]
    public function details(DesignerTeam $designerTeam): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();

        // Vérifier que l'utilisateur est membre ou propriétaire de l'équipe
        $isMember = $designerTeam->getMembers()->contains($currentUser);
        $isOwner = $designerTeam->getOwner() === $currentUser;

        if (!$isMember && !$isOwner) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette équipe');
        }

        return $this->render('designer/team/details.html.twig', [
            'designerTeam' => $designerTeam,
            'isOwner' => $isOwner,
            'currentUser' => $currentUser,
        ]);
    }

    #[Route('/designer/team/create', name: 'app_designer_team_create')]
    public function create(): Response
    {
        return $this->render('designer/team/create.html.twig');
    }

    #[Route('/designer/team/search-users', name: 'api_designer_search_users', methods: ['GET'])]
    public function searchUsers(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $users = $userRepository->searchUsersByQuery($query);

        $result = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'nickname' => $user->getNickname(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFirstName().' '.$user->getLastName(),
            ];
        }, $users);

        return $this->json($result);
    }

    #[Route('/designer/team/create-team', name: 'api_designer_create_team', methods: ['POST'])]
    public function createTeam(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ImageUploadService $imageUploadService,
    ): JsonResponse {
        try {
            // Récupérer les données depuis FormData
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $membersJson = $request->request->get('members');

            // Validation
            if (empty($name)) {
                return $this->json(['error' => 'Le nom de l\'équipe est requis'], Response::HTTP_BAD_REQUEST);
            }

            /** @var User $currentUser */
            $currentUser = $this->security->getUser();

            // Créer la nouvelle équipe
            $designerTeam = new DesignerTeam();
            $designerTeam->setName($name);
            $designerTeam->setDescription($description);
            $designerTeam->setOwner($currentUser);
            $designerTeam->addMember($currentUser);

            // Gérer l'upload d'image si présente
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $request->files->get('image');

            if ($uploadedFile) {
                try {
                    $picture = $imageUploadService->uploadImage($uploadedFile);
                    $designerTeam->setImage($picture);
                } catch (\InvalidArgumentException $e) {
                    return $this->json([
                        'error' => 'Erreur lors de l\'upload de l\'image',
                        'details' => $e->getMessage(),
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Ajouter les membres
            if (!empty($membersJson)) {
                $memberIds = json_decode($membersJson, true);
                if (is_array($memberIds)) {
                    foreach ($memberIds as $memberId) {
                        $member = $userRepository->find($memberId);
                        if ($member) {
                            $designerTeam->addMember($member);
                        }
                    }
                }
            }

            $entityManager->persist($designerTeam);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Équipe créée avec succès',
                'team' => [
                    'id' => $designerTeam->getId(),
                    'name' => $designerTeam->getName(),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la création de l\'équipe',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
