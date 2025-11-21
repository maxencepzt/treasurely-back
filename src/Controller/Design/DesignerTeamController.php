<?php

namespace App\Controller\Design;

use App\Entity\DesignerTeam;
use App\Entity\User;
use App\Repository\DesignerTeamRepository;
use App\Repository\TreasureHuntRepository;
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

    #[Route('/designer/team/{id}/details', name: 'app_designer_team_details')]
    public function details(DesignerTeam $designerTeam, TreasureHuntRepository $treasureHuntRepository): Response
    {
        // Vérifier les permissions de consultation
        $this->denyAccessUnlessGranted('DESIGNER_TEAM_VIEW', $designerTeam);

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();

        $isOwner = $designerTeam->getOwner() === $currentUser;

        $hunts = $treasureHuntRepository->findByDesignerTeam($designerTeam);

        return $this->render('designer/team/details.html.twig', [
            'designerTeam' => $designerTeam,
            'isOwner' => $isOwner,
            'canEdit' => $this->isGranted('DESIGNER_TEAM_EDIT', $designerTeam),
            'canDelete' => $this->isGranted('DESIGNER_TEAM_DELETE', $designerTeam),
            'treasureHunts' => $hunts,
        ]);
    }

    #[Route('/designer/team/create', name: 'app_designer_team_create')]
    public function create(): Response
    {
        return $this->render('designer/team/create.html.twig');
    }

    #[Route('/designer/team/{id}/edit', name: 'app_designer_team_edit')]
    public function edit(DesignerTeam $designerTeam, UserRepository $userRepository): Response
    {
        // Vérifier les permissions de modification
        $this->denyAccessUnlessGranted('DESIGNER_TEAM_EDIT', $designerTeam);

        return $this->render('designer/team/edit.html.twig', [
            'designerTeam' => $designerTeam,
            'members' => $userRepository->findByDesignerTeam($designerTeam),
        ]);
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

    #[Route('/designer/team/create-team', name: 'api_designer_team_create', methods: ['POST'])]
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

    #[Route('/designer/team/{id}/update', name: 'api_designer_team_update', methods: ['POST'])]
    public function updateTeam(
        DesignerTeam $designerTeam,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ImageUploadService $imageUploadService,
    ): JsonResponse {
        // Vérifier les permissions de modification
        $this->denyAccessUnlessGranted('DESIGNER_TEAM_EDIT', $designerTeam);

        try {
            // Récupérer les données depuis FormData
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $membersJson = $request->request->get('members');

            // Validation
            if (empty($name)) {
                return $this->json(['error' => 'Le nom de l\'équipe est requis'], Response::HTTP_BAD_REQUEST);
            }

            $designerTeam->setName($name);
            $designerTeam->setDescription($description);

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

            // Mettre à jour les membres
            if (!empty($membersJson)) {
                $memberIds = json_decode($membersJson, true);
                if (is_array($memberIds)) {
                    // Retirer tous les membres sauf le propriétaire
                    $owner = $designerTeam->getOwner();
                    foreach ($designerTeam->getMembers() as $member) {
                        if ($member !== $owner) {
                            $designerTeam->removeMember($member);
                        }
                    }

                    // Ajouter les nouveaux membres
                    foreach ($memberIds as $memberId) {
                        $member = $userRepository->find($memberId);
                        if ($member) {
                            $designerTeam->addMember($member);
                        }
                    }

                    // S'assurer que le propriétaire est toujours membre
                    if (!$designerTeam->getMembers()->contains($owner)) {
                        $designerTeam->addMember($owner);
                    }
                }
            }

            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Équipe modifiée avec succès',
                'team' => [
                    'id' => $designerTeam->getId(),
                    'name' => $designerTeam->getName(),
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la modification de l\'équipe',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/designer/team/{id}/delete', name: 'api_designer_team_delete', methods: ['DELETE'])]
    public function deleteTeam(
        DesignerTeam $designerTeam,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // Vérifier les permissions de suppression
        $this->denyAccessUnlessGranted('DESIGNER_TEAM_DELETE', $designerTeam);

        try {
            $teamName = $designerTeam->getName();
            $entityManager->remove($designerTeam);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => sprintf('L\'équipe "%s" a été supprimée avec succès', $teamName),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la suppression de l\'équipe',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/designer/team/{id}/leave', name: 'api_designer_team_leave', methods: ['POST'])]
    public function leaveTeam(
        DesignerTeam $designerTeam,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // Vérifier que l'utilisateur peut voir l'équipe (donc en est membre)
        $this->denyAccessUnlessGranted('DESIGNER_TEAM_VIEW', $designerTeam);

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();

        // Empêcher le propriétaire de quitter sa propre équipe
        if ($designerTeam->getOwner() === $currentUser) {
            return $this->json([
                'error' => 'Le propriétaire ne peut pas quitter son équipe. Vous devez d\'abord la supprimer.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $designerTeam->removeMember($currentUser);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Vous avez quitté l\'équipe avec succès',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
