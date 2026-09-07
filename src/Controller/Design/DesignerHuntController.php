<?php

namespace App\Controller\Design;

use App\Dto\Designer\HuntInput;
use App\Entity\QRRiddle;
use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Repository\DesignerTeamRepository;
use App\Repository\HuntTypeRepository;
use App\Repository\ParticipateHuntRepository;
use App\Repository\RiddleRepository;
use App\Repository\TreasureHuntRepository;
use App\Security\TreasureHuntVoter;
use App\Service\Designer\Exception\HuntConflictException;
use App\Service\Designer\Exception\HuntValidationException;
use App\Service\Designer\HuntEditor;
use App\Service\Designer\RiddleMapper;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Façade de conception des chasses. Les pages sont rendues en Twig ; les mutations
 * reçoivent le FormData de hunt-form.js et répondent en JSON.
 */
#[Route('/designer/hunt')]
final class DesignerHuntController extends AbstractController
{
    public const string CSRF_TOKEN_ID = 'designer_hunt';

    #[Route('', name: 'app_designer_hunt', methods: ['GET'])]
    public function index(#[CurrentUser] User $user, TreasureHuntRepository $treasureHuntRepository, ParticipateHuntRepository $participateHuntRepository): Response
    {
        $countByStatus = $treasureHuntRepository->countByStatusForOwner($user);
        $treasureHunts = $treasureHuntRepository->findByOwner($user);

        return $this->render('designer/hunt/index.html.twig', [
            'treasureHunts' => $treasureHunts,
            'participations' => $participateHuntRepository->countByHunt($treasureHunts),
            'nbDrafts' => $countByStatus[TreasureHunt::STATE_DRAFT] ?? 0,
            'nbOpened' => $countByStatus[TreasureHunt::STATE_OPENED] ?? 0,
            'nbClosed' => $countByStatus[TreasureHunt::STATE_CLOSED] ?? 0,
        ]);
    }

    #[Route('/{id}/details', name: 'app_designer_hunt_details', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(TreasureHuntVoter::VIEW, 'treasureHunt')]
    public function details(TreasureHunt $treasureHunt, RiddleRepository $riddleRepository, RiddleMapper $riddleMapper, ParticipateHuntRepository $participateHuntRepository, WorkflowInterface $treasureHuntWorkflow): Response
    {
        return $this->render('designer/hunt/details.html.twig', [
            'treasureHunt' => $treasureHunt,
            'riddles' => array_map($riddleMapper->toArray(...), $riddleRepository->findByTreasureHunt($treasureHunt)),
            'stats' => $participateHuntRepository->statsForHunt($treasureHunt),
            'ranking' => \array_slice($participateHuntRepository->findRanking($treasureHunt), 0, 10),
            'transitions' => array_map(
                static fn ($transition): string => $transition->getName(),
                $treasureHuntWorkflow->getEnabledTransitions($treasureHunt),
            ),
            'canEdit' => $this->isGranted(TreasureHuntVoter::EDIT, $treasureHunt),
        ]);
    }

    /**
     * Le QR code à imprimer et à placer sur le terrain. Il encode l'adresse de l'énigme dans
     * l'application des joueurs, code compris : l'appareil photo du téléphone y mène directement,
     * et le scanner intégré comme la saisie manuelle acceptent le même code.
     */
    #[Route('/{id}/riddle/{riddle}/qr.svg', name: 'app_designer_riddle_qr', requirements: ['id' => '\d+', 'riddle' => '\d+'], methods: ['GET'])]
    #[IsGranted(TreasureHuntVoter::VIEW, 'treasureHunt')]
    public function qrCode(
        TreasureHunt $treasureHunt,
        #[MapEntity(id: 'riddle')] Riddle $riddle,
        #[Autowire(env: 'FRONTEND_URL')] string $frontUrl,
    ): Response {
        if (!$riddle instanceof QRRiddle || $riddle->getHunt() !== $treasureHunt) {
            throw $this->createNotFoundException("Cette chasse n'a pas cette énigme QR.");
        }
        $target = rtrim($frontUrl, '/').'/riddle/'.$riddle->getId().'?code='.$riddle->getCode();
        $svg = (new Builder(writer: new SvgWriter(), data: $target, size: 320, margin: 12))->build()->getString();

        return new Response($svg, Response::HTTP_OK, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => sprintf('inline; filename="%s.svg"', $riddle->getCode()),
        ]);
    }

    #[Route('/create', name: 'app_designer_hunt_create', methods: ['GET'])]
    public function create(
        Request $request,
        #[CurrentUser] User $user,
        DesignerTeamRepository $designerTeamRepository,
        HuntTypeRepository $huntTypeRepository,
    ): Response {
        return $this->render('designer/hunt/create.html.twig', [
            'designerTeamId' => $request->query->getInt('designerTeamId') ?: null,
            'designerTeams' => $designerTeamRepository->findByMemberOrOwner($user),
            'huntTypes' => $huntTypeRepository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_designer_hunt_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(TreasureHuntVoter::EDIT, 'treasureHunt')]
    public function edit(
        TreasureHunt $treasureHunt,
        #[CurrentUser] User $user,
        DesignerTeamRepository $designerTeamRepository,
        HuntTypeRepository $huntTypeRepository,
        RiddleRepository $riddleRepository,
        RiddleMapper $riddleMapper,
    ): Response {
        return $this->render('designer/hunt/edit.html.twig', [
            'hunt' => $treasureHunt,
            'designerTeams' => $designerTeamRepository->findByMemberOrOwner($user),
            'huntTypes' => $huntTypeRepository->findAll(),
            'riddles' => array_map($riddleMapper->toArray(...), $riddleRepository->findByTreasureHunt($treasureHunt)),
        ]);
    }

    #[Route('/create', name: 'api_designer_hunt_create', methods: ['POST'])]
    public function store(Request $request, #[CurrentUser] User $user, HuntEditor $huntEditor): JsonResponse
    {
        $this->assertCsrfToken($request);

        try {
            $hunt = $huntEditor->create(HuntInput::fromRequest($request), $user);
        } catch (HuntValidationException|HuntConflictException $e) {
            return $this->failure($e);
        }

        return $this->json([
            'message' => 'Chasse créée avec succès.',
            'hunt' => ['id' => $hunt->getId(), 'title' => $hunt->getTitle()],
            'url' => $this->generateUrl('app_designer_hunt_details', ['id' => $hunt->getId()]),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/edit', name: 'api_designer_hunt_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(TreasureHuntVoter::EDIT, 'treasureHunt')]
    public function update(TreasureHunt $treasureHunt, Request $request, #[CurrentUser] User $user, HuntEditor $huntEditor): JsonResponse
    {
        $this->assertCsrfToken($request);

        try {
            $huntEditor->update($treasureHunt, HuntInput::fromRequest($request), $user);
        } catch (HuntValidationException|HuntConflictException $e) {
            return $this->failure($e);
        }

        return $this->json([
            'message' => 'Chasse modifiée avec succès.',
            'hunt' => ['id' => $treasureHunt->getId(), 'title' => $treasureHunt->getTitle()],
            'url' => $this->generateUrl('app_designer_hunt_details', ['id' => $treasureHunt->getId()]),
        ]);
    }

    /**
     * Fermer ou republier une chasse : les seules transitions que le workflow autorise
     * depuis la page de détails. La publication initiale passe par le formulaire.
     */
    #[Route('/{id}/transition/{transition}', name: 'api_designer_hunt_transition', requirements: ['id' => '\d+', 'transition' => 'close|republish'], methods: ['POST'])]
    #[IsGranted(TreasureHuntVoter::EDIT, 'treasureHunt')]
    public function transition(TreasureHunt $treasureHunt, string $transition, Request $request, WorkflowInterface $treasureHuntWorkflow, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->assertCsrfToken($request);

        if (!$treasureHuntWorkflow->can($treasureHunt, $transition)) {
            return $this->json(['error' => 'Cette action n\'est pas possible dans l\'état actuel de la chasse.'], Response::HTTP_CONFLICT);
        }

        $treasureHuntWorkflow->apply($treasureHunt, $transition);
        $entityManager->flush();

        return $this->json(['message' => 'Statut mis à jour.', 'status' => $treasureHunt->getStatus()]);
    }

    private function assertCsrfToken(Request $request): void
    {
        $token = $request->request->get('_token');
        if (!is_string($token) || !$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }

    private function failure(HuntValidationException|HuntConflictException $e): JsonResponse
    {
        if ($e instanceof HuntConflictException) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'error' => implode(' ', $e->getMessages()),
            'violations' => $e->getMessages(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
