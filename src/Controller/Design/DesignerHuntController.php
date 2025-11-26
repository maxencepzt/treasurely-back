<?php

namespace App\Controller\Design;

use App\Entity\GPSRiddle;
use App\Entity\MCQRiddle;
use App\Entity\QRRiddle;
use App\Entity\Riddle;
use App\Entity\TextRiddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Repository\DesignerTeamRepository;
use App\Repository\HuntTypeRepository;
use App\Repository\RiddleRepository;
use App\Repository\TreasureHuntRepository;
use App\Security\TreasureHuntVoter;
use App\Service\ImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DesignerHuntController extends AbstractController
{
    public function __construct(private readonly Security $security)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRiddlesWithTypes(RiddleRepository $riddleRepository, TreasureHunt $treasureHunt): array
    {
        $riddles = $riddleRepository->findByTreasureHunt($treasureHunt);
        $realRiddles = [];

        foreach ($riddles as $riddle) {
            $riddleData = [
                'title' => $riddle->getTitle(),
                'description' => $riddle->getDescription(),
                'difficulty' => $riddle->getDifficulty(),
                'orderNumber' => $riddle->getOrderNumber(),
            ];

            // Déterminer le type et ajouter les champs spécifiques
            switch ($riddle::class) {
                case TextRiddle::class:
                    /* @var TextRiddle $riddle */
                    $riddleData['type'] = 'text';
                    $riddleData['answer'] = $riddle->getAnswer();
                    break;
                case GPSRiddle::class:
                    /* @var GPSRiddle $riddle */
                    $riddleData['type'] = 'gps';
                    $riddleData['latitude'] = $riddle->getLatitude();
                    $riddleData['longitude'] = $riddle->getLongitude();
                    break;
                case MCQRiddle::class:
                    /* @var MCQRiddle $riddle */
                    $riddleData['type'] = 'mcq';
                    $riddleData['choices'] = $riddle->getChoices();
                    $riddleData['answers'] = $riddle->getAnswers();
                    break;
                case QRRiddle::class:
                    /* @var QRRiddle $riddle */
                    $riddleData['type'] = 'qr';
                    $riddleData['code'] = $riddle->getCode();
                    break;
                default:
                    $riddleData['type'] = 'text';
            }

            $realRiddles[] = $riddleData;
        }

        return $realRiddles;
    }

    #[Route('/designer/hunt', name: 'app_designer_hunt')]
    public function index(TreasureHuntRepository $treasureHuntRepository): Response
    {
        /**
         * @var User $currentUser
         */
        $currentUser = $this->security->getUser();

        $treasureHunts = $treasureHuntRepository->findByOwner($currentUser);
        $countStatus = $treasureHuntRepository->countStatusByOwner($currentUser);

        return $this->render('designer/hunt/index.html.twig', [
            'treasureHunts' => $treasureHunts,
            'nbDrafts' => $countStatus[TreasureHunt::STATE_DRAFT] ?? 0,
            'nbOpened' => $countStatus[TreasureHunt::STATE_OPENED] ?? 0,
            'nbClosed' => $countStatus[TreasureHunt::STATE_CLOSED] ?? 0,
        ]);
    }

    #[Route('/designer/hunt/{id}/details', name: 'app_designer_hunt_details')]
    public function details(TreasureHunt $treasureHunt, RiddleRepository $riddleRepository): Response
    {
        return $this->render('designer/hunt/details.html.twig', [
            'treasureHunt' => $treasureHunt,
            'riddles' => $this->getRiddlesWithTypes($riddleRepository, $treasureHunt),
        ]);
    }

    #[Route('/designer/hunt/create', name: 'app_designer_hunt_create_get', methods: ['GET'])]
    public function create(Request $request, DesignerTeamRepository $designerTeamRepository, HuntTypeRepository $huntTypeRepository): Response
    {
        $designerTeamId = $request->query->get('designerTeamId');

        /**
         * @var User $currentUser
         */
        $currentUser = $this->security->getUser();

        $designerTeams = $designerTeamRepository->findByMemberOrOwner($currentUser);
        $huntTypes = $huntTypeRepository->findAll();

        return $this->render('designer/hunt/create.html.twig', [
            'designerTeamId' => $designerTeamId,
            'designerTeams' => $designerTeams,
            'huntTypes' => $huntTypes,
        ]);
    }

    #[Route('/designer/hunt/{id}/edit', name: 'app_designer_hunt_edit', methods: ['GET'])]
    public function edit(
        TreasureHunt $treasureHunt,
        HuntTypeRepository $huntTypeRepository,
        DesignerTeamRepository $designerTeamRepository,
        RiddleRepository $riddleRepository,
    ): Response {
        /**
         * @var User $currentUser
         */
        $currentUser = $this->security->getUser();

        $designerTeams = $designerTeamRepository->findByMemberOrOwner($currentUser);
        $huntTypes = $huntTypeRepository->findAll();

        return $this->render('designer/hunt/edit.html.twig', [
            'hunt' => $treasureHunt,
            'designerTeamId' => $treasureHunt->getDesignerTeam()->getId(),
            'designerTeams' => $designerTeams,
            'huntTypes' => $huntTypes,
            'riddles' => $this->getRiddlesWithTypes($riddleRepository, $treasureHunt),
        ]);
    }

    #[Route('/designer/hunt/create', name: 'api_designer_hunt_create_post', methods: ['POST'])]
    public function createHunt(
        Request $request,
        DesignerTeamRepository $designerTeamRepository,
        HuntTypeRepository $huntTypeRepository,
        EntityManagerInterface $entityManager,
        ImageUploadService $imageUploadService,
    ): JsonResponse {
        try {
            // Récupérer les données depuis FormData
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $designerTeamId = $request->request->get('designer_team_id');
            $huntTypesJson = $request->request->get('hunt_types');
            $difficulty = $request->request->get('difficulty');
            $estimatedDuration = $request->request->get('estimated_duration');
            $city = $request->request->get('city');
            $action = $request->request->get('action', 'draft'); // 'draft' ou 'publish'
            $riddlesJson = $request->request->get('riddles');

            // Validation
            if (empty($name)) {
                return $this->json(['error' => 'Le nom de la chasse est requis'], Response::HTTP_BAD_REQUEST);
            }

            if (empty($description)) {
                return $this->json(['error' => 'La description est requise'], Response::HTTP_BAD_REQUEST);
            }

            if (empty($designerTeamId)) {
                return $this->json(['error' => 'L\'équipe est requise'], Response::HTTP_BAD_REQUEST);
            }

            if (empty($city)) {
                return $this->json(['error' => 'La ville est requise'], Response::HTTP_BAD_REQUEST);
            }

            /** @var User $currentUser */
            $currentUser = $this->security->getUser();

            // Récupérer l'équipe
            $designerTeam = $designerTeamRepository->find($designerTeamId);
            if (!$designerTeam) {
                return $this->json(['error' => 'Équipe non trouvée'], Response::HTTP_NOT_FOUND);
            }

            // Vérifier que l'utilisateur fait partie de l'équipe
            if (!$designerTeam->hasMember($currentUser) && $designerTeam->getOwner() !== $currentUser) {
                return $this->json(['error' => 'Vous ne faites pas partie de cette équipe'], Response::HTTP_FORBIDDEN);
            }

            // Créer la chasse
            $treasureHunt = new TreasureHunt();
            $treasureHunt->setTitle($name);
            $treasureHunt->setDescription($description);
            $treasureHunt->setOwner($currentUser);
            $treasureHunt->setDesignerTeam($designerTeam);
            $treasureHunt->setLocation($city);
            $treasureHunt->setEstimatedTime((int) $estimatedDuration);
            $treasureHunt->setDifficulty($difficulty);

            // Définir le statut selon l'action
            $treasureHunt->setStatus('publish' === $action ? TreasureHunt::STATE_OPENED : TreasureHunt::STATE_DRAFT);

            // Initialiser le compteur d'énigmes
            $treasureHunt->setRiddleCount(0);

            // Gérer les types de chasse
            if (!empty($huntTypesJson)) {
                $huntTypeIds = json_decode($huntTypesJson, true);
                if (is_array($huntTypeIds)) {
                    foreach ($huntTypeIds as $huntTypeId) {
                        $huntType = $huntTypeRepository->find($huntTypeId);
                        if ($huntType) {
                            $treasureHunt->addHuntType($huntType);
                        }
                    }
                }
            }

            // Gérer l'upload d'image si présente
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $request->files->get('image');
            if ($uploadedFile) {
                try {
                    $picture = $imageUploadService->uploadImage($uploadedFile);
                    $treasureHunt->setImage($picture);
                } catch (\InvalidArgumentException $e) {
                    return $this->json([
                        'error' => 'Erreur lors de l\'upload de l\'image',
                        'details' => $e->getMessage(),
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Gérer les énigmes
            if (!empty($riddlesJson)) {
                $riddles = json_decode($riddlesJson, true);
                if (is_array($riddles)) {
                    $orderNumber = 1;
                    foreach ($riddles as $riddleData) {
                        $riddle = $this->createRiddleFromData($riddleData, $treasureHunt, $orderNumber);
                        if ($riddle) {
                            $entityManager->persist($riddle);
                            ++$orderNumber;
                        }
                    }
                    $treasureHunt->setRiddleCount(count($riddles));
                }
            }

            $entityManager->persist($treasureHunt);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Chasse créée avec succès',
                'hunt' => [
                    'id' => $treasureHunt->getId(),
                    'name' => $treasureHunt->getTitle(),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la création de la chasse',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/designer/hunt/{id}/edit', name: 'api_designer_hunt_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateHunt(
        ?TreasureHunt $treasureHunt,
        Request $request,
        DesignerTeamRepository $designerTeamRepository,
        HuntTypeRepository $huntTypeRepository,
        EntityManagerInterface $entityManager,
        ImageUploadService $imageUploadService,
        RiddleRepository $riddleRepository,
    ): JsonResponse {
        if (!$treasureHunt) {
            return $this->json(['error' => 'Chasse non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier les permissions de modification avec le voter
        $this->denyAccessUnlessGranted(TreasureHuntVoter::EDIT, $treasureHunt);

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();

        try {
            // Récupérer les données depuis FormData
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $designerTeamId = $request->request->get('designer_team_id');
            $huntTypesJson = $request->request->get('hunt_types');
            $difficulty = $request->request->get('difficulty');
            $estimatedDuration = $request->request->get('estimated_duration');
            $isPublic = '1' === $request->request->get('is_public');
            $city = $request->request->get('city');
            $action = $request->request->get('action', 'draft');
            $riddlesJson = $request->request->get('riddles');

            // Validation
            if (empty($name)) {
                return $this->json(['error' => 'Le nom de la chasse est requis'], Response::HTTP_BAD_REQUEST);
            }

            if (empty($description)) {
                return $this->json(['error' => 'La description est requise'], Response::HTTP_BAD_REQUEST);
            }

            // Mettre à jour les informations de base
            $treasureHunt->setTitle($name);
            $treasureHunt->setDescription($description);
            $treasureHunt->setLocation($city);
            $treasureHunt->setEstimatedTime((int) $estimatedDuration);

            // Mapper la difficulté
            $difficultyMap = ['facile' => 1, 'moyen' => 2, 'difficile' => 3];
            $treasureHunt->setDifficulty($difficultyMap[$difficulty] ?? 2);

            // Mettre à jour le statut selon l'action
            if ('publish' === $action) {
                $treasureHunt->setStatus(TreasureHunt::STATE_OPENED);
            }

            // Mettre à jour l'équipe si changée
            if ($designerTeamId && $designerTeamId != $treasureHunt->getDesignerTeam()->getId()) {
                $designerTeam = $designerTeamRepository->find($designerTeamId);
                if ($designerTeam && ($designerTeam->hasMember($currentUser) || $designerTeam->getOwner() === $currentUser)) {
                    $treasureHunt->setDesignerTeam($designerTeam);
                }
            }

            // Mettre à jour les types de chasse
            if (!empty($huntTypesJson)) {
                // Supprimer les anciens types
                foreach ($treasureHunt->getHuntType() as $oldHuntType) {
                    $treasureHunt->removeHuntType($oldHuntType);
                }

                // Ajouter les nouveaux types
                $huntTypeIds = json_decode($huntTypesJson, true);
                if (is_array($huntTypeIds)) {
                    foreach ($huntTypeIds as $huntTypeId) {
                        $huntType = $huntTypeRepository->find($huntTypeId);
                        if ($huntType) {
                            $treasureHunt->addHuntType($huntType);
                        }
                    }
                }
            }

            // Gérer l'upload d'image si présente
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $request->files->get('image');
            if ($uploadedFile) {
                try {
                    $picture = $imageUploadService->uploadImage($uploadedFile);
                    $treasureHunt->setImage($picture);
                } catch (\InvalidArgumentException $e) {
                    return $this->json([
                        'error' => 'Erreur lors de l\'upload de l\'image',
                        'details' => $e->getMessage(),
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Gérer les énigmes
            if (!empty($riddlesJson)) {
                // Supprimer les anciennes énigmes
                $oldRiddles = $riddleRepository->findByTreasureHunt($treasureHunt);
                foreach ($oldRiddles as $oldRiddle) {
                    $entityManager->remove($oldRiddle);
                }

                // Créer les nouvelles énigmes
                $riddles = json_decode($riddlesJson, true);
                if (is_array($riddles)) {
                    $orderNumber = 1;
                    foreach ($riddles as $riddleData) {
                        $riddle = $this->createRiddleFromData($riddleData, $treasureHunt, $orderNumber);
                        if ($riddle) {
                            $entityManager->persist($riddle);
                            ++$orderNumber;
                        }
                    }
                    $treasureHunt->setRiddleCount(count($riddles));
                }
            }

            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Chasse modifiée avec succès',
                'hunt' => [
                    'id' => $treasureHunt->getId(),
                    'name' => $treasureHunt->getTitle(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la modification de la chasse',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createRiddleFromData(array $data, TreasureHunt $hunt, int $orderNumber): ?Riddle
    {
        $type = $data['type'] ?? null;
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $difficulty = (int) ($data['difficulty'] ?? 2);

        if (empty($title) || empty($type)) {
            return null;
        }

        $riddle = match ($type) {
            'text' => new TextRiddle(),
            'gps' => new GPSRiddle(),
            'mcq' => new MCQRiddle(),
            'qr' => new QRRiddle(),
            default => null,
        };

        if (!$riddle) {
            return null;
        }

        $riddle->setTitle($title);
        $riddle->setDescription($description);
        $riddle->setDifficulty($difficulty);
        $riddle->setOrderNumber($orderNumber);
        $riddle->setHunt($hunt);

        // Définir les données spécifiques selon le type
        match ($type) {
            'text' => $riddle->setAnswer($data['answer'] ?? ''),
            'gps' => $riddle->setLatitude($data['latitude'] ?? 0.0)->setLongitude($data['longitude'] ?? 0.0),
            'mcq' => $riddle->setChoices($data['choices'] ?? [])->setAnswers($data['answers'] ?? []),
            'qr' => $riddle->setCode($data['code'] ?? ''),
            default => null,
        };

        return $riddle;
    }
}
