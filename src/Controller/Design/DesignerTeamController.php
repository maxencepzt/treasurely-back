<?php

namespace App\Controller\Design;

use App\Entity\Team;
use App\Entity\User;
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
    public function index(EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $currentUser */
        $currentUser = $this->security->getUser();

        if (!$currentUser) {
            return $this->redirectToRoute('sso_redirect_login');
        }

        $teamRepository = $entityManager->getRepository(Team::class);

        // Récupérer les équipes dont l'utilisateur est propriétaire
        $ownedTeams = $teamRepository->findByOwner($currentUser);

        // Récupérer les équipes dont l'utilisateur est membre (mais pas propriétaire)
        $memberTeams = $teamRepository->findByMember($currentUser);

        return $this->render('designer/team/index.html.twig', [
            'ownedTeams' => $ownedTeams,
            'memberTeams' => $memberTeams,
        ]);
    }

    #[Route('/designer/team/details/{id}', name: 'app_designer_team_details')]
    public function details(int $id, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $currentUser */
        $currentUser = $this->security->getUser();

        if (!$currentUser) {
            return $this->redirectToRoute('sso_redirect_login');
        }

        $teamRepository = $entityManager->getRepository(Team::class);
        $team = $teamRepository->find($id);

        if (!$team) {
            throw $this->createNotFoundException('Équipe non trouvée');
        }

        // Vérifier que l'utilisateur est membre ou propriétaire de l'équipe
        $isMember = $team->getMembers()->contains($currentUser);
        $isOwner = $team->getOwner() === $currentUser;

        if (!$isMember && !$isOwner) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette équipe');
        }

        return $this->render('designer/team/details.html.twig', [
            'team' => $team,
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

        $qb = $userRepository->createQueryBuilder('u')
            ->where('u.nickname LIKE :query OR u.firstname LIKE :query OR u.lastname LIKE :query')
            ->andWhere('u.activated = true')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults(10);

        $users = $qb->getQuery()->getResult();

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

            /** @var User|null $currentUser */
            $currentUser = $this->security->getUser();

            if (!$currentUser) {
                return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
            }

            // Créer la nouvelle équipe
            $team = new Team();
            $team->setName($name);
            $team->setDescription($description);
            $team->setOwner($currentUser);

            // Gérer l'upload d'image si présente
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $request->files->get('image');

            if ($uploadedFile) {
                try {
                    $picture = $imageUploadService->uploadImage($uploadedFile);
                    $team->setImage($picture);
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
                            $team->addMember($member);
                        }
                    }
                }
            }

            $entityManager->persist($team);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Équipe créée avec succès',
                'team' => [
                    'id' => $team->getId(),
                    'name' => $team->getName(),
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
