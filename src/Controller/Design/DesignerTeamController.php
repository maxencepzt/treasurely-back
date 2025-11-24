<?php

namespace App\Controller\Design;

use App\Entity\DesignerTeam;
use App\Entity\User;
use App\Repository\DesignerTeamRepository;
use App\Repository\TreasureHuntRepository;
use App\Repository\UserRepository;
use App\Security\DesignerTeamVoter;
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

    #[Route('/designer/team/{id}/details', name: 'app_designer_team_details', requirements: ['id' => '\d+'])]
    public function details(?DesignerTeam $designerTeam, TreasureHuntRepository $treasureHuntRepository): Response
    {
        if (!$designerTeam) {
            return $this->redirectToRoute('app_designer_team');
        }

        // Vérifier les permissions de consultation
        $this->denyAccessUnlessGranted(DesignerTeamVoter::VIEW, $designerTeam);

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();

        $isOwner = $designerTeam->getOwner() === $currentUser;
        $isMember = $designerTeam->hasMember($currentUser);

        $hunts = $treasureHuntRepository->findByDesignerTeam($designerTeam);

        return $this->render('designer/team/details.html.twig', [
            'designerTeam' => $designerTeam,
            'isOwner' => $isOwner,
            'isMember' => $isMember,
            'canEdit' => $this->isGranted(DesignerTeamVoter::EDIT, $designerTeam),
            'canDelete' => $this->isGranted(DesignerTeamVoter::DELETE, $designerTeam),
            'treasureHunts' => $hunts,
        ]);
    }

    #[Route('/designer/team/create', name: 'app_designer_team_create', methods: ['GET'])]
    public function create(): Response
    {
        return $this->render('designer/team/create.html.twig');
    }

    #[Route('/designer/team/{id}/edit', name: 'app_designer_team_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function edit(?DesignerTeam $designerTeam, UserRepository $userRepository): Response
    {
        if (!$designerTeam) {
            return $this->redirectToRoute('app_designer_team');
        }

        // Vérifier les permissions de modification
        $this->denyAccessUnlessGranted(DesignerTeamVoter::EDIT, $designerTeam);

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

    #[Route('/designer/team/create', name: 'api_designer_team_create', methods: ['POST'])]
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

    #[Route('/designer/team/{id}/edit', name: 'api_designer_team_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateTeam(
        ?DesignerTeam $designerTeam,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ImageUploadService $imageUploadService,
    ): JsonResponse {
        if (!$designerTeam) {
            return $this->json(['error' => 'Équipe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier les permissions de modification
        $this->denyAccessUnlessGranted(DesignerTeamVoter::EDIT, $designerTeam);

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

    #[Route('/designer/team/{id}/delete', name: 'api_designer_team_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteTeam(
        ?DesignerTeam $designerTeam,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$designerTeam) {
            return $this->json(['error' => 'Équipe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier les permissions de suppression
        $this->denyAccessUnlessGranted(DesignerTeamVoter::DELETE, $designerTeam);

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

    #[Route('/designer/team/{id}/leave', name: 'api_designer_team_leave', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function leaveTeam(
        ?DesignerTeam $designerTeam,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$designerTeam) {
            return $this->json(['error' => 'Équipe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur peut voir l'équipe (donc en est membre)
        $this->denyAccessUnlessGranted(DesignerTeamVoter::VIEW, $designerTeam);

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

    #[Route('/designer/team/{id}/add-member/{userId}', name: 'api_designer_team_add_member', requirements: ['id' => '\d+', 'userId' => '\d+'], methods: ['POST'])]
    public function addMemberToTeam(
        ?DesignerTeam $designerTeam,
        int $userId,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$designerTeam) {
            return $this->json(['error' => 'Équipe non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier les permissions de modification
        $this->denyAccessUnlessGranted(DesignerTeamVoter::EDIT, $designerTeam);

        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur est déjà membre
        if ($designerTeam->hasMember($user)) {
            return $this->json(['error' => 'L\'utilisateur est déjà membre de l\'équipe'], Response::HTTP_BAD_REQUEST);
        }

        $designerTeam->addMember($user);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Membre ajouté avec succès',
        ], Response::HTTP_OK);
    }

    #[Route('/designer/team/{id}/remove-member/{userId}', name: 'api_designer_team_remove_member', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function removeMemberFromTeam(
        ?DesignerTeam $designerTeam,
        int $userId,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$designerTeam) {
            return $this->json(['error' => 'Équipe non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier les permissions de modification
        $this->denyAccessUnlessGranted(DesignerTeamVoter::EDIT, $designerTeam);

        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur est membre
        if (!$designerTeam->hasMember($user)) {
            return $this->json(['error' => 'L\'utilisateur n\'est pas membre de l\'équipe'], Response::HTTP_BAD_REQUEST);
        }

        // Empêcher de retirer le propriétaire
        if ($designerTeam->getOwner() === $user) {
            return $this->json(['error' => 'Le propriétaire ne peut pas être retiré de l\'équipe'], Response::HTTP_BAD_REQUEST);
        }

        $designerTeam->removeMember($user);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Membre retiré avec succès',
        ], Response::HTTP_OK);
    }
}
