<?php

namespace App\Controller\Design;

use App\Entity\GPSRiddle;
use App\Entity\MCQRiddle;
use App\Entity\QRRiddle;
use App\Entity\TextRiddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Repository\DesignerTeamRepository;
use App\Repository\RiddleRepository;
use App\Repository\TreasureHuntRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DesignerHuntController extends AbstractController
{
    public function __construct(private readonly Security $security)
    {
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

    #[Route('/designer/hunt/details/{id}', name: 'app_designer_hunt_details')]
    public function details(TreasureHunt $treasureHunt, RiddleRepository $riddleRepository): Response
    {
        $riddles = $riddleRepository->findByTreasureHunt($treasureHunt);
        $realRiddles = [];

        foreach ($riddles as $riddle) {
            $realRiddles[] = [
                'title' => $riddle->getTitle(),
                'description' => $riddle->getDescription(),
                'difficulty' => $riddle->getDifficulty(),
                'orderNumber' => $riddle->getOrderNumber(),
                'type' => match ($riddle::class) {
                    TextRiddle::class => 'Textuelle',
                    GPSRiddle::class => 'GPS',
                    MCQRiddle::class => 'QCM',
                    QRRiddle::class => 'QR Code',
                    default => 'Unknown',
                },
            ];
        }

        return $this->render('designer/hunt/details.html.twig', [
            'treasureHunt' => $treasureHunt,
            'riddles' => $realRiddles,
        ]);
    }

    #[Route('/designer/hunt/create', name: 'app_designer_hunt_create')]
    public function create(Request $request, DesignerTeamRepository $designerTeamRepository): Response
    {
        $designerTeamId = $request->query->get('designerTeamId');

        /**
         * @var User $currentUser
         */
        $currentUser = $this->security->getUser();

        $designerTeams = $designerTeamRepository->findByMemberOrOwner($currentUser);

        return $this->render('designer/hunt/create.html.twig', [
            'designerTeamId' => $designerTeamId,
            'designerTeams' => $designerTeams,
        ]);
    }
}
